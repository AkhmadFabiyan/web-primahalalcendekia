<?php

namespace App\Filament\Resources\Projects\ProjectArchiveResource\Pages;

use App\Traits\RecordsRecentlyViewed;

use App\Filament\Resources\Projects\ProjectArchiveResource;
use App\Modules\Projects\Enums\ArchiveVisibility;
use App\Modules\Projects\Enums\ProjectArchiveStatus;
use App\Modules\Projects\Jobs\GenerateProjectArchiveZipJob;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewProjectArchive extends ViewRecord
{
    use RecordsRecentlyViewed;

    protected static string $resource = ProjectArchiveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generate_zip')
                ->label('Buat Paket Arsip (Internal)')
                ->color('primary')
                ->icon('heroicon-o-archive-box')
                ->visible(fn () => auth()->user()->can('archives.generate'))
                ->action(function () {
                    GenerateProjectArchiveZipJob::dispatch($this->record, ArchiveVisibility::INTERNAL);
                    \Filament\Notifications\Notification::make()
                        ->title('Proses pembuatan paket arsip (Internal) telah dimasukkan ke dalam antrean.')
                        ->success()
                        ->send();
                }),
            Actions\Action::make('generate_zip_client')
                ->label('Buat Paket Arsip (Klien)')
                ->color('secondary')
                ->icon('heroicon-o-archive-box')
                ->visible(fn () => auth()->user()->can('archives.generate'))
                ->action(function () {
                    GenerateProjectArchiveZipJob::dispatch($this->record, ArchiveVisibility::CLIENT);
                    \Filament\Notifications\Notification::make()
                        ->title('Proses pembuatan paket arsip (Klien) telah dimasukkan ke dalam antrean.')
                        ->success()
                        ->send();
                }),
            Actions\Action::make('download_zip_internal')
                ->label('Unduh ZIP (Internal)')
                ->color('success')
                ->icon('heroicon-o-arrow-down-tray')
                ->visible(fn () => auth()->user()->can('archives.download_internal') && $this->record->status === ProjectArchiveStatus::READY && $this->record->hasMedia('archive-internal'))
                ->action(function () {
                    $media = $this->record->getFirstMedia('archive-internal');
                    return response()->download($media->getPath(), $media->file_name);
                }),
            Actions\Action::make('download_zip_client')
                ->label('Unduh ZIP (Klien)')
                ->color('success')
                ->icon('heroicon-o-arrow-down-tray')
                ->visible(fn () => auth()->user()->can('archives.download_client') && $this->record->status === ProjectArchiveStatus::READY && $this->record->hasMedia('archive-client'))
                ->action(function () {
                    $media = $this->record->getFirstMedia('archive-client');
                    return response()->download($media->getPath(), $media->file_name);
                }),
        ];
    }
}

