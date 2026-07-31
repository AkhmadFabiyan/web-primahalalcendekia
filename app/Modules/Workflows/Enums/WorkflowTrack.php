<?php

namespace App\Modules\Workflows\Enums;

enum WorkflowTrack: string
{
    case ENTRY = 'ENTRY';
    case COMPANION = 'COMPANION';
    case AUDITOR = 'AUDITOR';
}
