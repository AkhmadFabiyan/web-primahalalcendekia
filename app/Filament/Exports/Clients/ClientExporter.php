<?php

namespace App\Filament\Exports\Clients;

use App\Modules\Clients\Models\Client;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Str;

class ClientExporter extends Exporter
{
    protected static ?string $model = Client::class;

    public static function getColumns(): array
    {
        $sanitize = function ($state) {
            if (is_string($state) && preg_match('/^[=\+\-@]/', $state)) {
                return "'" . $state;
            }
            return $state;
        };

        return [
            ExportColumn::make('client_id')->label('Client ID'),
            ExportColumn::make('company_name')->label('Nama Perusahaan')->formatStateUsing($sanitize),
            ExportColumn::make('business_sector')->label('Sektor Bisnis')->formatStateUsing($sanitize),
            ExportColumn::make('address')->label('Alamat')->formatStateUsing($sanitize),
            ExportColumn::make('city')->label('Kota')->formatStateUsing($sanitize),
            ExportColumn::make('province')->label('Provinsi')->formatStateUsing($sanitize),
            ExportColumn::make('pic_name')->label('Nama PIC')->formatStateUsing($sanitize),
            ExportColumn::make('pic_phone')->label('Telepon PIC')->formatStateUsing($sanitize),
            ExportColumn::make('pic_email')->label('Email PIC')->formatStateUsing($sanitize),
            ExportColumn::make('client_type')->label('Tipe Klien')->state(fn(Client $record) => $record->client_type?->value),
            ExportColumn::make('partner.name')->label('Mitra')->formatStateUsing($sanitize),
            ExportColumn::make('created_at')->label('Dibuat Pada'),
        ];
    }

    public static function completed(Export $export): void {
        \Spatie\Activitylog\Facades\Activity::causedBy($export->user)
            ->withProperties(['format' => 'CSV/XLSX', 'status' => 'COMPLETED', 'rows' => $export->successful_rows])
            ->log('CLIENT_EXPORT_COMPLETED');
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export data Client selesai dan ' . number_format($export->successful_rows) . ' baris berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' baris gagal diekspor.';
        }

        return $body;
    }
}
