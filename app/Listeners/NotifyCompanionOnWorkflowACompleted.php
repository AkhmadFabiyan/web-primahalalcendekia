<?php

namespace App\Listeners;

use App\Events\WorkflowACompleted;
use App\Modules\Projects\Enums\AssignmentRole;
use App\Modules\Projects\Models\Project;
use App\Modules\Workflows\Enums\WorkflowStatus;
use App\Modules\Workflows\Models\WorkflowStep;
use App\Modules\Notifications\Enums\NotificationEvent;
use App\Modules\Notifications\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyCompanionOnWorkflowACompleted
{
    /**
     * Create the event listener.
     */
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(WorkflowACompleted $event): void
    {
        $project = Project::find($event->projectId);
        if (!$project) return;

        // Check Assignment Pendamping
        $assignment = $project->assignments()
            ->where('assignment_role', AssignmentRole::PENDAMPING_AUDITOR->value)
            ->whereNull('ended_at')
            ->first();

        // Need to check companion progress
        $companionTracker = WorkflowStep::where('project_id', $project->id)
            ->where('step_code', 'COMPANION_PROGRESS')
            ->first();

        if ($assignment && $assignment->user && $companionTracker) {
            if ($companionTracker->status === WorkflowStatus::COMPANION_NOT_PROCESSED) {
                // Notifikasi ke Pendamping
                $this->notificationService->send(
                    recipient: $assignment->user,
                    event: NotificationEvent::ENTRY_APPROVED,
                    title: 'Workflow Entry Selesai',
                    message: "Workflow Entry {$project->project_name} telah selesai. Project siap ditindaklanjuti pada proses audit.",
                    project: $project
                );
            }
        }
    }
}
