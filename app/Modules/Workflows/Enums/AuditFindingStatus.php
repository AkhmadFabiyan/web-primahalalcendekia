<?php

namespace App\Modules\Workflows\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;

enum AuditFindingStatus: string implements HasLabel, HasColor
{
    case OPEN = 'OPEN';
    case CORRECTION_REQUIRED = 'CORRECTION_REQUIRED';
    case EVIDENCE_SUBMITTED = 'EVIDENCE_SUBMITTED';
    case ACCEPTED = 'ACCEPTED';
    case VOIDED = 'VOIDED';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::OPEN => 'Terbuka',
            self::CORRECTION_REQUIRED => 'Perlu Perbaikan',
            self::EVIDENCE_SUBMITTED => 'Bukti Diserahkan',
            self::ACCEPTED => 'Diterima',
            self::VOIDED => 'Dibatalkan',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::OPEN => 'warning',
            self::CORRECTION_REQUIRED => 'danger',
            self::EVIDENCE_SUBMITTED => 'info',
            self::ACCEPTED => 'success',
            self::VOIDED => 'gray',
        };
    }
}
