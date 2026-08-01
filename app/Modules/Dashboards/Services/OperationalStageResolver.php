<?php

namespace App\Modules\Dashboards\Services;

use App\Modules\Projects\Enums\ProjectStatus;
use App\Modules\Workflows\Enums\WorkflowStatus;

class OperationalStageResolver
{
    public const STAGE_ENTRY = 'Belum/Proses Entry';
    public const STAGE_PREP_AUDIT = 'Menunggu/Persiapan Audit';
    public const STAGE_AUDIT = 'Audit/Revisi';
    public const STAGE_FATWA = 'Sidang Fatwa/BPJPH';
    public const STAGE_CERT_ISSUED = 'Sertifikat Terbit';

    /**
     * Resolve the stage based on ProjectStatus and Workflow statuses.
     */
    public static function resolve(?string $projectStatus, ?string $companionStatus, ?string $auditorStatus): string
    {
        // 1. Sertifikat Terbit
        if (
            $projectStatus === ProjectStatus::CERTIFICATE_ISSUED->value ||
            $projectStatus === ProjectStatus::WAITING_SETTLEMENT->value ||
            $projectStatus === ProjectStatus::COMPLETED->value ||
            $auditorStatus === WorkflowStatus::HALAL_CERTIFICATE_ISSUED->value
        ) {
            return self::STAGE_CERT_ISSUED;
        }

        // 2. Sidang Fatwa / BPJPH (Auditor 82% - 95%)
        $fatwaStatuses = [
            WorkflowStatus::WAITING_FATWA_SESSION->value,
            WorkflowStatus::FATWA_SESSION_COMPLETED->value,
            WorkflowStatus::WAITING_BPJPH_ISSUANCE->value,
        ];
        if (in_array($auditorStatus, $fatwaStatuses, true)) {
            return self::STAGE_FATWA;
        }

        // 3. Audit/Revisi (Auditor 10% - 75% atau Pendamping >= 75%)
        $auditActiveStatuses = [
            // Auditor 10% - 75%
            WorkflowStatus::DOCUMENT_REVIEW->value,
            WorkflowStatus::WAITING_FIELD_AUDIT->value,
            WorkflowStatus::FIELD_AUDIT_COMPLETED->value,
            WorkflowStatus::NONCONFORMITY_FOUND->value,
            WorkflowStatus::WAITING_CORRECTIVE_EVIDENCE->value,
            WorkflowStatus::CORRECTION_ACCEPTED->value,
            WorkflowStatus::AUDIT_REPORT_COMPLETED->value,
        ];
        $companionHighStatuses = [
            // Pendamping >= 75%
            WorkflowStatus::AUDIT_IN_PROGRESS->value,
            WorkflowStatus::AUDIT_COMPLETED->value,
            WorkflowStatus::WAITING_CLIENT_CORRECTION->value,
            WorkflowStatus::ASSISTANCE_COMPLETED->value,
        ];

        if (
            in_array($auditorStatus, $auditActiveStatuses, true) ||
            in_array($companionStatus, $companionHighStatuses, true)
        ) {
            return self::STAGE_AUDIT;
        }

        // 4. Menunggu / Persiapan Audit (Pendamping 15% - 55%)
        $companionPrepStatuses = [
            WorkflowStatus::WAITING_AUDIT_SCHEDULE->value,
            WorkflowStatus::AUDIT_PREPARATION->value,
            WorkflowStatus::FIELD_EVIDENCE_INCOMPLETE->value,
            WorkflowStatus::AUDIT_SCHEDULED->value,
        ];

        if (in_array($companionStatus, $companionPrepStatuses, true)) {
            return self::STAGE_PREP_AUDIT;
        }

        // 5. Belum / Proses Entry
        return self::STAGE_ENTRY;
    }
}
