<?php

namespace App\Modules\Workflows\Enums;

enum WorkflowLane: string
{
    case A = 'A';
    case B = 'B';
    case FINAL = 'FINAL';
}
