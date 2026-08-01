<?php

namespace App\Filament\Exports;

use App\Modules\Projects\Models\Project;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

class ProjectReportExporter extends Exporter
{
    protected static ?string $model = Project::class;

    public static function getColumns(): array
    {
        $sanitize = function ($state) {
            if (is_string($state) && preg_match('/^[=\+\-@]/', $state)) {
                return "'" . $state;
            }
            return $state;
        };

        return [
            ExportColumn::make('client.client_id')->label('ID Klien'),
            ExportColumn::make('client.company_name')->label('Klien')->formatStateUsing($sanitize),
            ExportColumn::make('client.type')->label('Tipe Klien')->state(fn(Project $record) => $record->client?->type?->value),
            ExportColumn::make('client.partner.name')->label('Mitra')->formatStateUsing($sanitize),
            ExportColumn::make('status')->label('Status Project')->state(fn(Project $record) => $record->status?->value),
            ExportColumn::make('workflow_a_status')->label('Status Workflow A')->state(fn(Project $record) => $record->workflow_a_status?->value),
            ExportColumn::make('workflow_b_status')->label('Status Workflow B')->state(fn(Project $record) => $record->workflow_b_status?->value),
            ExportColumn::make('marketing.name')->label('Marketing')->state(fn(Project $record) => $record->marketing?->name ?? '-')->formatStateUsing($sanitize),
            ExportColumn::make('admin.name')->label('Admin')->state(fn(Project $record) => $record->admin?->name ?? '-')->formatStateUsing($sanitize),
            ExportColumn::make('entry.name')->label('Entry')->state(fn(Project $record) => $record->entry?->name ?? '-')->formatStateUsing($sanitize),
            ExportColumn::make('auditor.name')->label('Auditor')->state(fn(Project $record) => $record->auditor?->name ?? '-')->formatStateUsing($sanitize),
        ];
    }

    public static function completed(Export $export): void { \Spatie\Activitylog\Facades\Activity::causedBy($export->user)->withProperties(['format' => 'CSV/XLSX', 'status' => 'COMPLETED'])->log('REPORT_EXPORT_COMPLETED'); }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Ekspor Laporan Project Anda telah selesai dan ' . number_format($export->successful_rows) . ' baris berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' baris gagal diekspor.';
        }

        return $body;
    }
}
