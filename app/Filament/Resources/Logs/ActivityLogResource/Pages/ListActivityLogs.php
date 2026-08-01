<?php

namespace App\Filament\Resources\Logs\ActivityLogResource\Pages;

use App\Filament\Resources\Logs\ActivityLogResource;
use Filament\Resources\Pages\ListRecords;

class ListActivityLogs extends ListRecords
{
    protected static string $resource = ActivityLogResource::class;
}
