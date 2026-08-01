<?php

namespace App\Modules\Workflows\Enums;

enum SlaDurationUnit: string
{
    case MINUTES = 'MINUTES';
    case HOURS = 'HOURS';
    case BUSINESS_DAYS = 'BUSINESS_DAYS';
    case SCHEDULED_DATE = 'SCHEDULED_DATE';
}
