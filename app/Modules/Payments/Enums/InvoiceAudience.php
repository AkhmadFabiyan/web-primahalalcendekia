<?php

namespace App\Modules\Payments\Enums;

use Filament\Support\Contracts\HasLabel;

enum InvoiceAudience: string implements HasLabel
{
    case CLIENT = 'CLIENT';
    case PARTNER = 'PARTNER';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::CLIENT => 'Klien',
            self::PARTNER => 'Mitra',
        };
    }
}
