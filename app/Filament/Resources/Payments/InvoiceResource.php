<?php

namespace App\Filament\Resources\Payments;

use App\Filament\Resources\Payments\Pages;
use App\Filament\Resources\Payments\Infolists\InvoiceWorkspaceInfolist;
use App\Modules\Payments\Models\Invoice;
use App\Modules\Payments\Enums\InvoiceStatus;
use App\Modules\Payments\Services\InvoiceActionService;
use App\Modules\Payments\Services\PaymentService;
use Filament\Forms\Form;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Database\Eloquent\Builder;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;
    
    // Custom slug as requested in ui/invoice.md
    protected static ?string $slug = 'payments/invoices';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-currency-dollar';
    protected static string|\UnitEnum|null $navigationGroup = 'Pembayaran';
    protected static ?string $modelLabel = 'Invoice';
    protected static ?string $pluralModelLabel = 'Invoices';

    // Disable creating manually
    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
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

    public static function infolist(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
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
                    ->badge(),
                TextColumn::make('invoice_type')
                    ->label('Jenis')
                    ->badge(),
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
                ViewAction::make()->slideOver(),
                EditAction::make()
                    ->modalWidth(MaxWidth::Medium)
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
                            ->required()
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
                            ->whereIn('status', [\App\Modules\Payments\Enums\PaymentStatus::VERIFIED, \App\Modules\Payments\Enums\PaymentStatus::PENDING])
                            ->sum('amount');
                        $availableBalance = $record->total - $verifiedAndPending;
                        
                        return [
                            Placeholder::make('invoice_info')
                                ->label('Informasi Tagihan')
                                ->content("Total: Rp " . number_format($record->total, 2, ',', '.') . 
                                          " | Belum Terbayar/Sedang Diproses: Rp " . number_format($availableBalance, 2, ',', '.')),
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
                                $proofFile = storage_path('app/public/' . $data['proof']); 
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
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageInvoices::route('/'),
        ];
    }
}
