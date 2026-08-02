<?php

namespace App\Filament\Resources\Payments\Infolists;

use App\Modules\Payments\Enums\InvoiceAudience;
use App\Modules\Payments\Enums\InvoiceStatus;
use App\Modules\Payments\Models\Invoice;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;

class InvoiceWorkspaceInfolist
{
    public static function schema(): array
    {
        return [
            Tabs::make('InvoiceDetail')
                ->tabs([
                    Tabs\Tab::make('Ringkasan')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            Grid::make(2)->schema([
                                Section::make('Informasi Invoice')
                                    ->schema([
                                        TextEntry::make('invoice_number')->label('Nomor Invoice')->default('-'),
                                        TextEntry::make('invoice_type')->label('Jenis Invoice')->badge(),
                                        TextEntry::make('audience')->label('Audience')->badge(),
                                        TextEntry::make('status')->label('Status')->badge(),
                                        TextEntry::make('due_date')->label('Jatuh Tempo')->date(),
                                        TextEntry::make('issued_at')->label('Tanggal Terbit')->dateTime()->placeholder('Belum terbit'),
                                        TextEntry::make('billing_group_id')->label('Billing Group ID'),
                                    ])->columnSpan(1),

                                Section::make('Informasi Nilai')
                                    ->schema([
                                        TextEntry::make('subtotal')->label('Subtotal')->money('IDR'),
                                        TextEntry::make('discount_total')->label('Diskon')->money('IDR'),
                                        TextEntry::make('total')->label('Total Tagihan')->money('IDR')->weight('bold'),
                                        TextEntry::make('notes')->label('Catatan')->placeholder('Tidak ada catatan.'),
                                        TextEntry::make('cancel_reason')->label('Alasan Batal')->placeholder('-')->visible(fn (Invoice $record) => $record->status === InvoiceStatus::CANCELLED),
                                    ])->columnSpan(1),

                                Section::make('Informasi Pihak Terkait')
                                    ->schema([
                                        TextEntry::make('project.client.business_id')->label('ID Klien'),
                                        TextEntry::make('project.client.company_name')->label('Klien'),
                                        TextEntry::make('partner.name')->label('Mitra')->visible(fn (Invoice $record) => $record->audience === InvoiceAudience::PARTNER),
                                    ])->columnSpanFull(),
                            ]),
                        ]),

                    Tabs\Tab::make('Pembayaran')
                        ->icon('heroicon-o-credit-card')
                        ->schema([
                            TextEntry::make('payment_placeholder')
                                ->hiddenLabel()
                                ->default('Tab Pembayaran akan diimplementasikan pada milestone selanjutnya.'),
                        ]),

                    Tabs\Tab::make('Timeline')
                        ->icon('heroicon-o-clock')
                        ->schema([
                            TextEntry::make('timeline_placeholder')
                                ->hiddenLabel()
                                ->default('Tab Timeline akan diimplementasikan pada milestone selanjutnya.'),
                        ]),

                    Tabs\Tab::make('Activity Log')
                        ->icon('heroicon-o-clipboard-document-list')
                        ->schema([
                            TextEntry::make('activity_placeholder')
                                ->hiddenLabel()
                                ->default('Activity Log akan diimplementasikan pada milestone selanjutnya.'),
                        ]),
                ])
                ->columnSpanFull(),
        ];
    }
}
