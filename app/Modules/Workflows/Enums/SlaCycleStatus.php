<?php

namespace App\Modules\Workflows\Enums;

enum SlaCycleStatus: string
{
    case ACTIVE = 'ACTIVE';
    case PAUSED = 'PAUSED';
    case MET = 'MET';
    case BREACHED = 'BREACHED';
    case CANCELLED = 'CANCELLED';
}
