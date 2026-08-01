<?php

namespace App\Filament\Resources\Projects\ProjectArchiveResource\Pages;

use App\Filament\Resources\Projects\ProjectArchiveResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Exports\ProjectReportExporter;
use Filament\Actions\ExportAction;

class ListProjectArchives extends ListRecords
{
    protected static string $resource = ProjectArchiveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->exporter(ProjectReportExporter::class)
                ->color('info')
                ->icon('heroicon-o-arrow-down-tray'),
        ];
    }
}
