<?php

namespace App\Filament\Resources\Logs\ActivityLogResource\Pages;

use App\Traits\RecordsRecentlyViewed;

use App\Filament\Resources\Logs\ActivityLogResource;
use Filament\Resources\Pages\ViewRecord;

class ViewActivityLog extends ViewRecord
{
    use RecordsRecentlyViewed;

    protected static string $resource = ActivityLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

