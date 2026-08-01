<?php

namespace App\Filament\Exports;

use App\Enums\InvoiceAudience;
use App\Modules\Payments\Models\Invoice;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class InvoiceBillingGroupExporter extends Exporter
{
    protected static ?string $model = Invoice::class;

    public static function getColumns(): array
    {
        $sanitize = function ($state) {
            if (is_string($state) && preg_match('/^[=\+\-@]/', $state)) {
                return "'" . $state;
            }
            return $state;
        };

        // Cache invoices for the group to avoid redundant queries per column
        $getGroup = function (Invoice $record) {
            static $cache = [];
            if (!isset($cache[$record->billing_group_id])) {
                $cache[$record->billing_group_id] = Invoice::where('billing_group_id', $record->billing_group_id)->get();
            }
            return $cache[$record->billing_group_id];
        };

        $getClientInvoice = fn(Invoice $record) => $getGroup($record)->firstWhere('audience', InvoiceAudience::CLIENT);
        $getPartnerInvoice = fn(Invoice $record) => $getGroup($record)->firstWhere('audience', InvoiceAudience::PARTNER);

        return [
            ExportColumn::make('billing_group_id')->label('Billing Group ID')->formatStateUsing($sanitize),
            ExportColumn::make('project.client.client_id')->label('ID Klien')->formatStateUsing($sanitize),
            ExportColumn::make('project.client.company_name')->label('Nama Klien')->formatStateUsing($sanitize),
            ExportColumn::make('project.client.type')->label('Tipe Klien')->state(fn(Invoice $record) => $record->project?->client?->type?->value),
            ExportColumn::make('type')->label('Jenis Invoice')->state(fn(Invoice $record) => $record->type?->value),
            
            // Client data
            ExportColumn::make('invoice_client_number')->label('Nomor Invoice Klien')
                ->state(fn(Invoice $record) => $getClientInvoice($record)?->invoice_number ?? '-')
                ->formatStateUsing($sanitize),
            
            // Partner data
            ExportColumn::make('invoice_partner_number')->label('Nomor Invoice Mitra')
                ->state(fn(Invoice $record) => $getPartnerInvoice($record)?->invoice_number ?? '-')
                ->formatStateUsing($sanitize),
            
            ExportColumn::make('nominal_client')->label('Nominal Klien')
                ->state(fn(Invoice $record) => $getClientInvoice($record)?->amount ?? 0),
                
            ExportColumn::make('nominal_partner')->label('Nominal Mitra')
                ->state(fn(Invoice $record) => $getPartnerInvoice($record)?->amount ?? 0),
                
            ExportColumn::make('paid_client')->label('Terbayar Klien')
                ->state(fn(Invoice $record) => $getClientInvoice($record)?->paid_amount ?? 0),
                
            ExportColumn::make('paid_partner')->label('Terbayar Mitra')
                ->state(fn(Invoice $record) => $getPartnerInvoice($record)?->paid_amount ?? 0),
                
            ExportColumn::make('outstanding_client')->label('Outstanding Klien')
                ->state(fn(Invoice $record) => max(0, ($getClientInvoice($record)?->amount ?? 0) - ($getClientInvoice($record)?->paid_amount ?? 0))),
                
            ExportColumn::make('outstanding_partner')->label('Outstanding Mitra')
                ->state(fn(Invoice $record) => max(0, ($getPartnerInvoice($record)?->amount ?? 0) - ($getPartnerInvoice($record)?->paid_amount ?? 0))),
                
            ExportColumn::make('total_group')->label('Total Nilai Group')
                ->state(fn(Invoice $record) => ($getClientInvoice($record)?->amount ?? 0) + ($getPartnerInvoice($record)?->amount ?? 0)),
                
            ExportColumn::make('status_client')->label('Status Klien')
                ->state(fn(Invoice $record) => $getClientInvoice($record)?->status?->value ?? '-'),
                
            ExportColumn::make('status_partner')->label('Status Mitra')
                ->state(fn(Invoice $record) => $getPartnerInvoice($record)?->status?->value ?? '-'),
                
            ExportColumn::make('status_group')->label('Status Billing Group')
                ->state(function (Invoice $record) use ($getClientInvoice, $getPartnerInvoice) {
                    $client = $getClientInvoice($record);
                    $partner = $getPartnerInvoice($record);
                    
                    if (!$client && !$partner) return '-';
                    
                    $statuses = array_filter([$client?->status?->value, $partner?->status?->value]);
                    
                    if (in_array('CANCELLED', $statuses)) return 'CANCELLED';
                    if (in_array('PENDING', $statuses) || in_array('UNVERIFIED', $statuses) || empty($statuses)) return 'PENDING';
                    if (count($statuses) > 1 && (in_array('PARTIAL', $statuses) || in_array('PENDING', $statuses))) return 'PARTIAL';
                    
                    // If all are paid
                    $allPaid = true;
                    foreach ($statuses as $s) {
                        if ($s !== 'PAID') $allPaid = false;
                    }
                    return $allPaid ? 'PAID' : 'PARTIAL';
                }),
                
            ExportColumn::make('published_date')->label('Tanggal Terbit')->date('Y-m-d'),
            ExportColumn::make('due_date')->label('Jatuh Tempo')->date('Y-m-d'),
        ];
    }

    public static function completed(Export $export): void { \Spatie\Activitylog\Facades\Activity::causedBy($export->user)->withProperties(['format' => 'CSV/XLSX', 'status' => 'COMPLETED'])->log('REPORT_EXPORT_COMPLETED'); }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Ekspor Laporan Invoice Billing Group Anda telah selesai dan ' . number_format($export->successful_rows) . ' baris berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' baris gagal diekspor.';
        }

        return $body;
    }
}
