<?php

namespace App\Modules\Payments\Enums;

use Filament\Support\Contracts\HasLabel;

enum InvoiceType: string implements HasLabel
{
    case ACTIVATION = 'ACTIVATION';
    case INSTALLMENT = 'INSTALLMENT';
    case GOVERNMENT = 'GOVERNMENT';
    case SETTLEMENT = 'SETTLEMENT';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::ACTIVATION => 'Invoice Aktivasi',
            self::INSTALLMENT => 'Invoice Termin',
            self::GOVERNMENT => 'Invoice Negara',
            self::SETTLEMENT => 'Invoice Pelunasan',
        };
    }
}
