<?php

namespace App\Modules\Workflows\Services;

use App\Modules\Projects\Models\Project;
use App\Modules\Workflows\Enums\WorkflowStatus;
use App\Modules\Workflows\Models\WorkflowStep;

class WorkflowProgressService
{
    /**
     * Mengambil dan menghitung progress dari masing-masing workflow.
     */
    public static function forProject(Project $project): array
    {
        $workflowA = WorkflowStep::where('project_id', $project->id)
            ->where('step_code', 'ENTRY_PROGRESS')
            ->first();

        $workflowB = WorkflowStep::where('project_id', $project->id)
            ->where('step_code', 'AUDITOR_PROGRESS')
            ->first();

        $entryStatus = $workflowA ? $workflowA->status->value : null;
        $entryLabel = $workflowA ? $workflowA->status->getLabel() : 'Belum Mulai';
        $entryCompleted = $entryStatus === WorkflowStatus::ENTRY_COMPLETED->value;
        $entryPercentage = self::calculateEntryPercentage($entryStatus);

        $auditorStatus = $workflowB ? $workflowB->status->value : null;
        $auditorLabel = $workflowB ? $workflowB->status->getLabel() : 'Belum Mulai';
        $auditorCompleted = $auditorStatus === WorkflowStatus::AUDIT_REPORT_COMPLETED->value;
        $auditorPercentage = self::calculateAuditorPercentage($auditorStatus);

        $completedCount = ($entryCompleted ? 1 : 0) + ($auditorCompleted ? 1 : 0);

        return [
            'entry' => [
                'status' => $entryStatus,
                'label' => $entryLabel,
                'percentage' => $entryPercentage,
                'completed' => $entryCompleted,
            ],
            'auditor' => [
                'status' => $auditorStatus,
                'label' => $auditorLabel,
                'percentage' => $auditorPercentage,
                'completed' => $auditorCompleted,
            ],
            'gate' => [
                'completed_workflows' => $completedCount,
                'required_workflows' => 2,
                'ready' => $completedCount === 2,
            ],
        ];
    }

    private static function calculateEntryPercentage(?string $status): int
    {
        if (!$status) return 0;
        
        $map = [
            WorkflowStatus::ENTRY_NOT_STARTED->value => 0,
            WorkflowStatus::WAITING_CLIENT_DOCUMENTS->value => 20,
            WorkflowStatus::DOCUMENTS_INCOMPLETE->value => 30,
            WorkflowStatus::CREATING_SIHALAL_ACCOUNT->value => 40,
            WorkflowStatus::PREPARING_SJPH_MANUAL->value => 60,
            WorkflowStatus::INPUTTING_MATERIALS_PRODUCTS->value => 80,
            WorkflowStatus::ENTRY_COMPLETED->value => 100,
        ];

        return $map[$status] ?? 0;
    }

    private static function calculateAuditorPercentage(?string $status): int
    {
        if (!$status) return 0;

        $map = [
            WorkflowStatus::AUDITOR_NOT_PROCESSED->value => 0,
            WorkflowStatus::DOCUMENT_REVIEW->value => 20,
            WorkflowStatus::WAITING_FIELD_AUDIT->value => 40,
            WorkflowStatus::FIELD_AUDIT_COMPLETED->value => 60,
            WorkflowStatus::NONCONFORMITY_FOUND->value => 50,
            WorkflowStatus::WAITING_CORRECTIVE_EVIDENCE->value => 65,
            WorkflowStatus::CORRECTION_ACCEPTED->value => 70,
            WorkflowStatus::AUDIT_REPORT_COMPLETED->value => 75,
            // Status di bawah ini terjadi di Phase 22 ke atas, tetapi untuk tampilan workflow B kita bisa asumsikan 100% jika sudah mencapai tahap ini
            WorkflowStatus::WAITING_FATWA_SESSION->value => 85,
            WorkflowStatus::FATWA_SESSION_COMPLETED->value => 100,
            WorkflowStatus::WAITING_BPJPH_ISSUANCE->value => 100,
            WorkflowStatus::HALAL_CERTIFICATE_ISSUED->value => 100,
        ];

        return $map[$status] ?? 0;
    }
}
