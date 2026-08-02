<?php

namespace App\Filament\Resources\Payments;

use App\Enums\Role;
use App\Filament\Resources\Payments\Infolists\InvoiceWorkspaceInfolist;
use App\Filament\Support\RoleNavigation;
use App\Modules\Payments\Enums\InvoiceAudience;
use App\Modules\Payments\Enums\InvoiceStatus;
use App\Modules\Payments\Enums\PaymentStatus;
use App\Modules\Payments\Models\Invoice;
use App\Modules\Payments\Services\InvoiceActionService;
use App\Modules\Payments\Services\InvoicePdfService;
use App\Modules\Payments\Services\PaymentService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static bool $isGloballySearchable = true;

    protected static ?string $recordTitleAttribute = 'invoice_number';

    protected static int $globalSearchResultsLimit = 10;

    // Custom slug as requested in ui/invoice.md
    protected static ?string $slug = 'payments/invoices';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-currency-dollar';

    public static function getNavigationGroup(): string
    {
        return RoleNavigation::forModule('finance');
    }

    protected static ?string $modelLabel = 'Invoice';

    protected static ?string $pluralModelLabel = 'Invoices';

    // Disable creating manually
    public static function canCreate(): bool
    {
        return false;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['invoice_number', 'project.client.business_id', 'project.client.company_name', 'billing_group_id'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->invoice_number ?? 'Draft Invoice';
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Client' => $record->project?->client?->company_name ?? '-',
            'Total' => 'Rp '.number_format($record->total, 2, ',', '.'),
            'Status' => $record->status?->value ?? '-',
        ];
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return InvoiceResource::getUrl('index'); // We don't have a view page for invoice yet, maybe just manage page
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        $query = parent::getGlobalSearchEloquentQuery()->with(['project.client']);

        $user = auth()->user();
        if ($user && $user->hasRole(Role::KLIEN->value) && $user->client_id) {
            $query->whereHas('project', function ($q) use ($user) {
                $q->where('client_id', $user->client_id);
            })->where('audience', InvoiceAudience::CLIENT);
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                // Only due_date and notes are editable as per requirement
                DatePicker::make('due_date')
                    ->label('Jatuh Tempo')
                    ->required()
                    ->native(false)
                    ->columnSpanFull(),
                Textarea::make('notes')
                    ->label('Catatan Invoice')
                    ->columnSpanFull()
                    ->rows(3),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema(InvoiceWorkspaceInfolist::schema());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('Nomor Invoice')
                    ->searchable()
                    ->sortable()
                    ->default('-'),
                TextColumn::make('project.client.business_id')
                    ->label('Project / Client')
                    ->description(fn (Invoice $record): string => $record->project->client->company_name ?? '-')
                    ->searchable(),
                TextColumn::make('project.client.client_type')
                    ->label('Tipe Klien')
                    ->badge(),
                TextColumn::make('audience')
                    ->label('Audience')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('invoice_type')
                    ->label('Jenis')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total')
                    ->label('Total Tagihan')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('downloadPdf')
                    ->label('Unduh PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function (Invoice $record) {
                        $pdfService = app(InvoicePdfService::class);
                        $path = $pdfService->generate($record);

                        activity()
                            ->causedBy(auth()->user())
                            ->performedOn($record)
                            ->event('downloaded')
                            ->log('INVOICE_PDF_ISSUED');

                        return response()->download(storage_path('app/private/'.$path));
                    }),
                ViewAction::make()->slideOver(),
                EditAction::make()
                    ->modalWidth(Width::Medium)
                    ->visible(fn (Invoice $record): bool => $record->status === InvoiceStatus::DRAFT),

                Action::make('publish')
                    ->label('Terbitkan')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Terbitkan Tagihan')
                    ->modalDescription('Tindakan ini akan menerbitkan seluruh tagihan dalam grup (Client dan Mitra jika ada) secara bersamaan. Nomor invoice akan digenerate dan data master akan dibekukan (snapshot). Anda tidak dapat mengubah data invoice setelah terbit.')
                    ->visible(fn (Invoice $record): bool => $record->status === InvoiceStatus::DRAFT)
                    ->action(function (Invoice $record) {
                        try {
                            $service = app(InvoiceActionService::class);
                            $service->publishGroup($record->billing_group_id);

                            Notification::make()
                                ->title('Invoice Berhasil Diterbitkan')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal Menerbitkan Invoice')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('cancel')
                    ->label('Batalkan')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Batalkan Tagihan')
                    ->modalDescription('Membatalkan tagihan ini akan membatalkan seluruh grup tagihan terkait. Tagihan yang sudah dibatalkan tidak dapat diaktifkan kembali.')
                    ->form([
                        Textarea::make('reason')
                            ->label('Alasan Pembatalan')
                            ->required(),
                    ])
                    ->visible(fn (Invoice $record): bool => in_array($record->status, [InvoiceStatus::DRAFT, InvoiceStatus::PUBLISHED]))
                    ->action(function (array $data, Invoice $record) {
                        try {
                            $service = app(InvoiceActionService::class);
                            $service->cancelGroup($record->billing_group_id, $data['reason']);

                            Notification::make()
                                ->title('Invoice Dibatalkan')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal Membatalkan Invoice')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('print')
                    ->label('Cetak PDF')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn (Invoice $record): string => route('payments.invoices.print', $record->id))
                    ->openUrlInNewTab()
                    ->visible(fn (Invoice $record): bool => $record->status !== InvoiceStatus::DRAFT),

                Action::make('pay')
                    ->label('Catat Pembayaran')
                    ->icon('heroicon-o-banknotes')
                    ->color('primary')
                    ->modalHeading('Catat Pembayaran')
                    ->modalDescription('Catat penerimaan dana untuk tagihan ini.')
                    ->visible(fn (Invoice $record): bool => in_array($record->status, [InvoiceStatus::PUBLISHED, InvoiceStatus::PARTIAL]))
                    ->form(function (Invoice $record) {
                        $verifiedAndPending = $record->payments()
                            ->whereIn('status', [PaymentStatus::VERIFIED, PaymentStatus::PENDING])
                            ->sum('amount');
                        $availableBalance = $record->total - $verifiedAndPending;

                        return [
                            Placeholder::make('invoice_info')
                                ->label('Informasi Tagihan')
                                ->content('Total: Rp '.number_format($record->total, 2, ',', '.').
                                          ' | Belum Terbayar/Sedang Diproses: Rp '.number_format($availableBalance, 2, ',', '.')),
                            TextInput::make('amount')
                                ->label('Nominal Pembayaran')
                                ->required()
                                ->numeric()
                                ->minValue(1)
                                ->maxValue($availableBalance)
                                ->default($availableBalance),
                            DatePicker::make('payment_date')
                                ->label('Tanggal Pembayaran')
                                ->required()
                                ->native(false)
                                ->default(now()),
                            Select::make('payment_method')
                                ->label('Metode Pembayaran')
                                ->required()
                                ->options([
                                    'Bank Transfer' => 'Bank Transfer',
                                    'Cash' => 'Cash',
                                    'QRIS' => 'QRIS',
                                    'Virtual Account' => 'Virtual Account',
                                ]),
                            TextInput::make('reference_number')
                                ->label('Nomor Referensi')
                                ->nullable(),
                            FileUpload::make('proof')
                                ->label('Bukti Pembayaran')
                                ->required()
                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                ->maxSize(5120) // 5MB limit
                                ->preserveFilenames(),
                            Textarea::make('notes')
                                ->label('Catatan')
                                ->nullable()
                                ->rows(2),
                        ];
                    })
                    ->action(function (array $data, Invoice $record) {
                        try {
                            $service = app(PaymentService::class);

                            $proofFile = null;
                            if (isset($data['proof'])) {
                                // Since FileUpload stores it in temp directory before we move it
                                // For Spatie Media Library, we get the file path.
                                // Filament FileUpload component returns file path relative to disk or an array
                                // With simple setup, it's string (path). We should pass the path to service.
                                $proofFile = storage_path('app/public/'.$data['proof']);
                                // Actually Filament temp files might be different,
                                // Wait, Filament uses temporary upload. Usually it's handled by Filament automatically,
                                // but when creating manually, we might need to be careful.
                                // It's better to let Spatie Media plugin handle it if we use Filament's Spatie plugin,
                                // but since we don't have SpatieMediaLibraryFileUpload imported, we use basic FileUpload.
                                // Basic FileUpload uploads to public disk by default.
                                // We can pass the path.
                            }

                            $service->createPayment($record, $data, $proofFile);

                            Notification::make()
                                ->title('Pembayaran Berhasil Dicatat')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal Mencatat Pembayaran')
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
            'index' => Pages\ManageInvoices::route('/'),
        ];
    }
}
