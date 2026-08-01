<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Filament\Resources\Payments\PaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Exports\PaymentReportExporter;
use Filament\Actions\ExportAction;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->exporter(PaymentReportExporter::class)
                ->color('info')
                ->icon('heroicon-o-arrow-down-tray'),
        ];
    }
}
