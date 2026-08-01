<?php

namespace App\Filament\Exports;

use App\Modules\Payments\Models\Payment;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

class PaymentReportExporter extends Exporter
{
    protected static ?string $model = Payment::class;

    public static function getColumns(): array
    {
        $sanitize = function ($state) {
            if (is_string($state) && preg_match('/^[=\+\-@]/', $state)) {
                return "'" . $state;
            }
            return $state;
        };

        return [
            ExportColumn::make('payment_number')->label('Nomor Pembayaran')->formatStateUsing($sanitize),
            ExportColumn::make('payment_date')->label('Tanggal Bayar')->date('Y-m-d'),
            ExportColumn::make('amount')->label('Nominal Pembayaran'),
            ExportColumn::make('payment_method')->label('Metode Pembayaran')->state(fn(Payment $record) => $record->payment_method?->value),
            ExportColumn::make('status')->label('Status Pembayaran')->state(fn(Payment $record) => $record->status?->value),
            ExportColumn::make('invoice.invoice_number')->label('Nomor Invoice')->formatStateUsing($sanitize),
            ExportColumn::make('invoice.project.client.company_name')->label('Klien')->formatStateUsing($sanitize),
        ];
    }

    public static function completed(Export $export): void { \Spatie\Activitylog\Facades\Activity::causedBy($export->user)->withProperties(['format' => 'CSV/XLSX', 'status' => 'COMPLETED'])->log('REPORT_EXPORT_COMPLETED'); }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Ekspor Laporan Payment Anda telah selesai dan ' . number_format($export->successful_rows) . ' baris berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' baris gagal diekspor.';
        }

        return $body;
    }
}
