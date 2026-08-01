<?php

namespace App\Modules\Workflows\Enums;

enum WorkflowReviewDecision: string
{
    case PENDING = 'PENDING';
    case APPROVED = 'APPROVED';
    case REVISION_REQUESTED = 'REVISION_REQUESTED';
}
