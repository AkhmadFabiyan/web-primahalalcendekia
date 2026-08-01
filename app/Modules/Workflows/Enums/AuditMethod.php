<?php

namespace App\Modules\Workflows\Enums;

enum AuditMethod: string
{
    case ONLINE = 'ONLINE';
    case ONSITE = 'ONSITE';

    public function label(): string
    {
        return match ($this) {
            self::ONLINE => 'Online',
            self::ONSITE => 'On-site',
        };
    }
}
