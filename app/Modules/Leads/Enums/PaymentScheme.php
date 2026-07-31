<?php

namespace App\Modules\Leads\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentScheme: string implements HasLabel
{
    case FULL_PAYMENT = 'FULL_PAYMENT';
    case INSTALLMENT = 'INSTALLMENT';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::FULL_PAYMENT => 'Sekali Bayar',
            self::INSTALLMENT => 'Termin',
        };
    }
}
