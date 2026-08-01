<?php

namespace App\Modules\Workflows\Enums;

enum TaskStatus: string
{
    case TODO = 'TODO';
    case IN_PROGRESS = 'IN_PROGRESS';
    case WAITING_REVIEW = 'WAITING_REVIEW';
    case REVISION = 'REVISION';
    case COMPLETED = 'COMPLETED';
    case CANCELLED = 'CANCELLED';
}
