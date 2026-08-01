<?php

namespace App\Modules\Workflows\Enums;

enum TaskType: string
{
    case DOCUMENT_COMPLETION = 'DOCUMENT_COMPLETION';
    case ENTRY_PROCESS = 'ENTRY_PROCESS';
    case SPV_ENTRY_REVIEW = 'SPV_ENTRY_REVIEW';
    case AUDIT_PLANNING = 'AUDIT_PLANNING';
    case AUDIT_EXECUTION = 'AUDIT_EXECUTION';
    case AUDITOR_REVIEW = 'AUDITOR_REVIEW';
}
