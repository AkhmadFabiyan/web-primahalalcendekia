<?php

namespace App\Filament\Resources\Partners\Pages;

use App\Traits\RecordsRecentlyViewed;

use App\Filament\Resources\Partners\PartnerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPartner extends EditRecord
{
    use RecordsRecentlyViewed;

    protected static string $resource = PartnerResource::class;

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


