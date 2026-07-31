<?php

namespace App\Modules\Workflows\Services;

use App\Modules\Projects\Models\Project;
use App\Modules\Workflows\Models\WorkflowStep;

class WorkflowService
{
    public function updateStepStatus(string $projectId, string $stepCode, string $lane, string $status, ?string $metadata = null, bool $isRequired = true): void
    {
        $step = WorkflowStep::firstOrCreate(
            [
                'project_id' => $projectId,
                'step_code' => $stepCode,
            ],
            [
                'workflow_lane' => $lane,
                'status' => $status,
                'is_required' => $isRequired,
            ]
        );

        if ($step->status !== $status) {
            $oldStatus = $step->status;
            $step->update(['status' => $status]);

            $step->histories()->create([
                'project_id' => $projectId,
                'from_status' => $oldStatus,
                'to_status' => $status,
                'metadata' => $metadata ? ['notes' => $metadata] : null,
            ]);
        }
    }

    public function getStep(string $projectId, string $stepCode): ?WorkflowStep
    {
        return WorkflowStep::where('project_id', $projectId)
            ->where('step_code', $stepCode)
            ->first();
    }
}
