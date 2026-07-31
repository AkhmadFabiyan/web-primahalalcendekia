<?php

namespace App\Modules\Clients\Enums;

use Filament\Support\Contracts\HasLabel;

enum ClientType: string implements HasLabel
{
    case DIRECT = 'DIRECT';
    case PARTNER = 'PARTNER';

    public function getLabel(): string
    {
        return match ($this) {
            self::DIRECT => 'Langsung',
            self::PARTNER => 'Mitra',
        };
    }
}
