<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Traits\RecordsRecentlyViewed;

use App\Filament\Resources\Leads\LeadResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLead extends EditRecord
{
    use RecordsRecentlyViewed;

    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }

    protected function getSaveFormAction(): \Filament\Actions\Action
    {
        return parent::getSaveFormAction()->keyBindings(['command+s', 'ctrl+s']);
    }
}


