<?php

namespace App\Modules\Workflows\Services;

use App\Modules\Projects\Models\Project;
use App\Modules\Workflows\Enums\WorkflowLane;
use App\Modules\Workflows\Enums\WorkflowStatus;
use App\Modules\Workflows\Enums\WorkflowTrack;
use App\Modules\Workflows\Models\WorkflowStep;

class WorkflowInitializationService
{
    /**
     * Inisialisasi 3 tracker utama untuk project yang baru diaktifkan.
     */
    public function initializeForProject(Project $project, string $actorId): void
    {
        $this->createTracker($project, WorkflowLane::A, WorkflowTrack::ENTRY, 'ENTRY_PROGRESS', WorkflowStatus::ENTRY_NOT_STARTED, $actorId);
        $this->createTracker($project, WorkflowLane::B, WorkflowTrack::COMPANION, 'COMPANION_PROGRESS', WorkflowStatus::COMPANION_NOT_PROCESSED, $actorId);
        $this->createTracker($project, WorkflowLane::B, WorkflowTrack::AUDITOR, 'AUDITOR_PROGRESS', WorkflowStatus::AUDITOR_NOT_PROCESSED, $actorId);
    }

    private function createTracker(
        Project $project,
        WorkflowLane $lane,
        WorkflowTrack $track,
        string $stepCode,
        WorkflowStatus $initialStatus,
        string $actorId
    ): void {
        $step = WorkflowStep::create([
            'project_id' => $project->id,
            'step_code' => $stepCode,
            'workflow_lane' => $lane->value,
            'track_code' => $track->value,
            'status' => $initialStatus->value,
            'is_required' => true,
            'last_changed_by' => $actorId,
        ]);

        $step->histories()->create([
            'project_id' => $project->id,
            'from_status' => null,
            'to_status' => $initialStatus->value,
            'actor_id' => $actorId,
            'metadata' => [
                'source' => 'SYSTEM'
            ],
        ]);
    }
}
