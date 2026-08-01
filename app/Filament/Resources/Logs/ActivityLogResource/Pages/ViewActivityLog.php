<?php

namespace App\Filament\Resources\Logs\ActivityLogResource\Pages;

use App\Filament\Resources\Logs\ActivityLogResource;
use Filament\Resources\Pages\ViewRecord;

class ViewActivityLog extends ViewRecord
{
    protected static string $resource = ActivityLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
