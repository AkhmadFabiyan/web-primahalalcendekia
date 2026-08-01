<?php

namespace App\Modules\Workflows\Enums;

enum SlaEventType: string
{
    case STARTED = 'STARTED';
    case REMINDER_SENT = 'REMINDER_SENT';
    case PAUSED = 'PAUSED';
    case RESUMED = 'RESUMED';
    case BREACHED = 'BREACHED';
    case ESCALATED_LEVEL_1 = 'ESCALATED_LEVEL_1';
    case ESCALATED_LEVEL_2 = 'ESCALATED_LEVEL_2';
    case COMPLETED = 'COMPLETED';
    case DEADLINE_ADJUSTED = 'DEADLINE_ADJUSTED';
    case CANCELLED = 'CANCELLED';
}
