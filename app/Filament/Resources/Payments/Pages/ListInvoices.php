<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Filament\Resources\Payments\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Exports\InvoiceBillingGroupExporter;
use Filament\Actions\ExportAction;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->exporter(InvoiceBillingGroupExporter::class)
                ->color('info')
                ->icon('heroicon-o-arrow-down-tray'),
        ];
    }
}
