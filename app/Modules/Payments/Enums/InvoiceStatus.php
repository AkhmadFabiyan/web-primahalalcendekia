<?php

namespace App\Modules\Payments\Enums;

use Filament\Support\Contracts\HasLabel;

enum InvoiceStatus: string implements HasLabel
{
    case DRAFT = 'DRAFT';
    case PUBLISHED = 'PUBLISHED';
    case PARTIAL = 'PARTIAL';
    case PAID = 'PAID';
    case CANCELLED = 'CANCELLED';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PUBLISHED => 'Diterbitkan',
            self::PARTIAL => 'Sebagian',
            self::PAID => 'Lunas',
            self::CANCELLED => 'Dibatalkan',
        };
    }
}
