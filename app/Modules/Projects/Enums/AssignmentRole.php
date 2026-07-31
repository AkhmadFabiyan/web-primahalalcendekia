<?php

namespace App\Modules\Projects\Enums;

use Filament\Support\Contracts\HasLabel;

enum AssignmentRole: string implements HasLabel
{
    case MARKETING = 'MARKETING';
    case FINANCE = 'FINANCE';
    case ADMIN = 'ADMIN';
    case ENTRY = 'ENTRY';
    case SPV_ENTRY = 'SPV_ENTRY';
    case PENDAMPING_AUDITOR = 'PENDAMPING_AUDITOR';
    case AUDITOR = 'AUDITOR';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::MARKETING => 'Marketing',
            self::FINANCE => 'Finance',
            self::ADMIN => 'Admin Perusahaan',
            self::ENTRY => 'Entry',
            self::SPV_ENTRY => 'SPV Entry',
            self::PENDAMPING_AUDITOR => 'Pendamping Auditor',
            self::AUDITOR => 'Auditor',
        };
    }
}
