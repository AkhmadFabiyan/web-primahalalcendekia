<?php

namespace App\Filament\Resources\DocumentAdministration\Pages;

use App\Filament\Resources\DocumentAdministration\DocumentAdministrationResource;
use Filament\Resources\Pages\ListRecords;

class ListDocumentAdministrations extends ListRecords
{
    protected static string $resource = DocumentAdministrationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
