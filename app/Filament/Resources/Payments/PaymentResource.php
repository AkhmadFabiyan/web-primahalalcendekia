<?php

namespace App\Filament\Resources\Payments;

use App\Filament\Resources\Payments\Pages;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Enums\PaymentStatus;
use App\Modules\Payments\Services\PaymentService;
use Filament\Forms\Form;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Section;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;
    protected static bool $isGloballySearchable = true;
    protected static ?string $recordTitleAttribute = 'payment_number';
    protected static int $globalSearchResultsLimit = 10;

    protected static ?string $slug = 'payments/transactions';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';
    protected static string|\UnitEnum|null $navigationGroup = 'Pembayaran';
    protected static ?string $modelLabel = 'Payment';
    protected static ?string $pluralModelLabel = 'Payments';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['payment_number', 'reference_number', 'invoice.invoice_number', 'invoice.project.client.business_id'];
    }

    public static function getGlobalSearchResultTitle(\Illuminate\Database\Eloquent\Model $record): string
    {
        return $record->payment_number ?? 'Pembayaran';
    }

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return [
            'Invoice' => $record->invoice?->invoice_number ?? '-',
            'Nominal' => 'Rp ' . number_format($record->amount, 2, ',', '.'),
            'Status' => $record->status?->value ?? '-',
        ];
    }

    public static function getGlobalSearchResultUrl(\Illuminate\Database\Eloquent\Model $record): string
    {
        return PaymentResource::getUrl('index'); // We don't have a view page for payment
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        $query = parent::getGlobalSearchEloquentQuery()->with(['invoice.project.client']);

        $user = auth()->user();
        if ($user && $user->hasRole(\App\Enums\Role::KLIEN->value) && $user->client_id) {
            $query->whereHas('invoice.project', function ($q) use ($user) {
                $q->where('client_id', $user->client_id);
            });
        }

        return $query;
    }

    public static function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->schema([
                //
            ]);
    }
    
    public static function infolist(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->schema([
                Tabs::make('Tabs')
                    ->tabs([
                        Tabs\Tab::make('Ringkasan')
                            ->schema([
                                TextEntry::make('payment_number')->label('Payment ID'),
                                TextEntry::make('invoice.invoice_number')->label('Invoice'),
                                TextEntry::make('invoice.project.client.company_name')->label('Client'),
                                TextEntry::make('amount')->label('Nominal')->money('IDR'),
                                TextEntry::make('payment_method')->label('Metode Pembayaran'),
                                TextEntry::make('reference_number')->label('Nomor Referensi'),
                                TextEntry::make('status')->label('Status')->badge(),
                            ]),
                        Tabs\Tab::make('Bukti Pembayaran')
                            ->schema([
                                TextEntry::make('proof')
                                    ->label('Preview Bukti')
                                    ->formatStateUsing(function (Payment $record) {
                                        $media = $record->getFirstMedia('payment-proofs');
                                        if (!$media) return 'Tidak ada bukti';
                                        
                                        $url = route('payments.proof.download', $record->id);
                                        return "<a href='{$url}' target='_blank' style='color:blue;text-decoration:underline;'>Lihat / Unduh File</a>";
                                    })
                                    ->html(),
                            ]),
                        Tabs\Tab::make('Verifikasi')
                            ->schema([
                                TextEntry::make('status')->label('Status')->badge(),
                                TextEntry::make('verifier.name')->label('Verifier'),
                                TextEntry::make('verified_at')->label('Waktu Verifikasi')->dateTime(),
                                TextEntry::make('verification_notes')->label('Catatan'),
                            ]),
                        Tabs\Tab::make('Timeline')
                            ->schema([
                                TextEntry::make('created_at')->label('Dibuat')->dateTime(),
                                TextEntry::make('verified_at')->label('Diverifikasi')->dateTime(),
                                TextEntry::make('rejected_at')->label('Ditolak')->dateTime(),
                            ]),
                    ])->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('payment_number')
                    ->label('Payment ID')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('invoice.invoice_number')
                    ->label('Invoice')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('invoice.project.client.company_name')
                    ->label('Client')
                    ->searchable(),
                TextColumn::make('amount')
                    ->label('Nominal')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->label('Metode')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('payment_date')
                    ->label('Payment Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                TextColumn::make('verifier.name')
                    ->label('Verified By')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('downloadReceipt')
                    ->label('Unduh Kwitansi')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn (\App\Modules\Payments\Models\Payment $record) => $record->status === \App\Modules\Payments\Enums\PaymentStatus::VERIFIED && $record->receipt)
                    ->action(function (\App\Modules\Payments\Models\Payment $record) {
                        $pdfService = app(\App\Modules\Payments\Services\ReceiptPdfService::class);
                        $path = $pdfService->generate($record->receipt);
                        
                        activity()
                            ->causedBy(auth()->user())
                            ->performedOn($record->receipt)
                            ->event('downloaded')
                            ->log('PAYMENT_RECEIPT_ISSUED');

                        return response()->download(storage_path('app/private/' . $path));
                    }),
                ViewAction::make()->slideOver(),

                Action::make('verify')
                    ->label('Verifikasi')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Verifikasi Pembayaran')
                    ->modalDescription('Apakah Anda yakin ingin memverifikasi pembayaran ini? Tindakan ini akan mengupdate status invoice terkait dan tidak dapat dibatalkan.')
                    ->visible(fn (Payment $record): bool => $record->status === PaymentStatus::PENDING)
                    ->form([
                        Textarea::make('verification_notes')
                            ->label('Catatan Verifikasi (Opsional)')
                    ])
                    ->action(function (array $data, Payment $record) {
                        try {
                            app(PaymentService::class)->verifyPayment($record, $data);

                            Notification::make()
                                ->title('Pembayaran Terverifikasi')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal Memverifikasi')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Tolak Pembayaran')
                    ->visible(fn (Payment $record): bool => $record->status === PaymentStatus::PENDING)
                    ->form([
                        Textarea::make('rejection_reason')
                            ->label('Alasan Penolakan')
                            ->required()
                    ])
                    ->action(function (array $data, Payment $record) {
                        try {
                            app(PaymentService::class)->rejectPayment($record, $data);

                            Notification::make()
                                ->title('Pembayaran Ditolak')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal Menolak')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                // Disable bulk actions
            ])
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePayments::route('/'),
        ];
    }
}
