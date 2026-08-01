<?php

namespace App\Filament\Exports;

use App\Modules\Leads\Models\Lead;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class LeadReportExporter extends Exporter
{
    protected static ?string $model = Lead::class;

    public static function getColumns(): array
    {
        $sanitize = function ($state) {
            if (is_string($state) && preg_match('/^[=\+\-@]/', $state)) {
                return "'" . $state;
            }
            return $state;
        };

        return [
            ExportColumn::make('id')->label('ID Lead'),
            ExportColumn::make('created_at')->label('Tanggal Lead')->date('Y-m-d'),
            ExportColumn::make('company_name')->label('Nama Perusahaan')->formatStateUsing($sanitize),
            ExportColumn::make('brand_name')->label('Nama Brand')->formatStateUsing($sanitize),
            ExportColumn::make('type')->label('Tipe Klien')->state(fn(Lead $record) => $record->type?->value),
            ExportColumn::make('partner.name')->label('Mitra')->formatStateUsing($sanitize),
            ExportColumn::make('marketing.name')->label('Marketing')->formatStateUsing($sanitize),
            ExportColumn::make('status')->label('Status Lead')->state(fn(Lead $record) => $record->status?->value),
            ExportColumn::make('nominal_client')->label('Nominal Klien'),
            ExportColumn::make('nominal_partner')->label('Nominal Mitra'),
        ];
    }

    public static function completed(Export $export): void { \Spatie\Activitylog\Facades\Activity::causedBy($export->user)->withProperties(['format' => 'CSV/XLSX', 'status' => 'COMPLETED'])->log('REPORT_EXPORT_COMPLETED'); }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Ekspor Laporan Lead Anda telah selesai dan ' . number_format($export->successful_rows) . ' baris berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' baris gagal diekspor.';
        }

        return $body;
    }
}
