<?php

namespace App\Modules\Projects\Enums;

use Filament\Support\Contracts\HasLabel;

enum ArchiveVisibility: string implements HasLabel
{
    case INTERNAL = 'INTERNAL';
    case CLIENT = 'CLIENT';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::INTERNAL => 'Internal',
            self::CLIENT => 'Klien',
        };
    }
}
