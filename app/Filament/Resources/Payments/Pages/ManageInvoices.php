<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Filament\Resources\Payments\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageInvoices extends ManageRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create actions
        ];
    }
}
