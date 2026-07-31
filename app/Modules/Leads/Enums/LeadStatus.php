<?php

namespace App\Modules\Leads\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;

enum LeadStatus: string implements HasLabel, HasColor
{
    case DRAFT = 'DRAFT';
    case DEAL = 'DEAL';
    case CANCELLED = 'CANCELLED';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::DEAL => 'Deal',
            self::CANCELLED => 'Batal',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::DRAFT => 'warning',
            self::DEAL => 'success',
            self::CANCELLED => 'danger',
        };
    }
}
