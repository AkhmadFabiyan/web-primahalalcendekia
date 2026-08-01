<?php

namespace App\Modules\Workflows\Services;

use App\Models\User;
use App\Modules\Projects\Enums\AssignmentRole;
use App\Modules\Projects\Enums\ProjectStatus;
use App\Modules\Projects\Models\Project;
use App\Modules\Workflows\Enums\TaskStatus;
use App\Modules\Workflows\Enums\WorkflowReviewDecision;
use App\Modules\Workflows\Enums\WorkflowStatus;
use App\Modules\Workflows\Models\Task;
use App\Modules\Workflows\Models\WorkflowHistory;
use App\Modules\Workflows\Models\WorkflowReview;
use App\Modules\Workflows\Models\WorkflowStep;
use Exception;
use Illuminate\Support\Facades\DB;

class SpvEntryWorkflowService
{
    public function startReview(Task $spvTask, User $actor): void
    {
        DB::transaction(function () use ($spvTask, $actor) {
            $spvTask = Task::where('id', $spvTask->id)->lockForUpdate()->firstOrFail();
            $entryTask = Task::where('id', $spvTask->parent_task_id)->lockForUpdate()->firstOrFail();
            
            $review = WorkflowReview::where('review_task_id', $spvTask->id)->lockForUpdate()->firstOrFail();
            $tracker = WorkflowStep::where('id', $review->workflow_step_id)->lockForUpdate()->firstOrFail();
            $project = Project::where('id', $spvTask->project_id)->firstOrFail();

            if ($spvTask->assigned_to !== $actor->id) {
                throw new Exception("Anda tidak berhak memulai review tugas ini.");
            }

            $assignment = $project->assignments()
                ->where('assignment_role', AssignmentRole::SPV_ENTRY->value)
                ->where('user_id', $actor->id)
                ->whereNull('ended_at')
                ->first();
                
            if (!$assignment) {
                throw new Exception("Penugasan SPV Entry Anda sudah tidak aktif.");
            }
            if ($actor->status !== 'ACTIVE' || !$actor->hasRole('SPV Entry')) {
                throw new Exception("Akun Anda tidak aktif atau tidak memiliki role SPV Entry.");
            }
            if ($spvTask->status !== TaskStatus::TODO) {
                throw new Exception("Review sudah dimulai atau telah selesai.");
            }
            if ($review->decision !== WorkflowReviewDecision::PENDING) {
                throw new Exception("Review ini sudah diputuskan.");
            }
            if ($tracker->status !== WorkflowStatus::SUBMITTED_TO_LPH) {
                throw new Exception("Status tracker bukan SUBMITTED_TO_LPH.");
            }
            if ($entryTask->status !== TaskStatus::WAITING_REVIEW) {
                throw new Exception("Task Entry tidak dalam status WAITING_REVIEW.");
            }
            
            // Cek apakah ini submission terbaru
            $latestSubmission = WorkflowReview::where('workflow_step_id', $tracker->id)
                ->orderBy('created_at', 'desc')
                ->first();
                
            if ($latestSubmission && $latestSubmission->id !== $review->id) {
                throw new Exception("Siklus review ini sudah tidak aktif.");
            }

            $spvTask->status = TaskStatus::IN_PROGRESS;
            if (!$spvTask->started_at) {
                $spvTask->started_at = now();
            }
            $spvTask->save();
            
            if (!$review->started_at) {
                $review->started_at = now();
                $review->save();
            }

            activity()
                ->performedOn($spvTask)
                ->causedBy($actor)
                ->event('started')
                ->log("Mulai melakukan review hasil Entry SIHALAL");
        });
    }

    public function approve(Task $spvTask, User $actor): void
    {
        DB::transaction(function () use ($spvTask, $actor) {
            $project = Project::where('id', $spvTask->project_id)->lockForUpdate()->firstOrFail();
            $spvTask = Task::where('id', $spvTask->id)->lockForUpdate()->firstOrFail();
            $entryTask = Task::where('id', $spvTask->parent_task_id)->lockForUpdate()->firstOrFail();
            
            $review = WorkflowReview::where('review_task_id', $spvTask->id)->lockForUpdate()->firstOrFail();
            $tracker = WorkflowStep::where('id', $review->workflow_step_id)->lockForUpdate()->firstOrFail();
            
            $workflowA = WorkflowStep::firstOrCreate(
                ['project_id' => $project->id, 'step_code' => 'WORKFLOW_A'],
                ['workflow_lane' => 'A', 'status' => WorkflowStatus::COMPLETE]
            );

            if ($spvTask->status !== TaskStatus::IN_PROGRESS) {
                throw new Exception("Task Review belum dimulai atau sudah selesai.");
            }
            if ($review->decision === WorkflowReviewDecision::APPROVED) {
                return; // Idempotent
            }
            if ($review->decision !== WorkflowReviewDecision::PENDING) {
                throw new Exception("Review telah diputuskan.");
            }
            if ($tracker->status !== WorkflowStatus::SUBMITTED_TO_LPH) {
                throw new Exception("Status tracker bukan SUBMITTED_TO_LPH.");
            }
            
            $assignment = $project->assignments()
                ->where('assignment_role', AssignmentRole::SPV_ENTRY->value)
                ->where('user_id', $actor->id)
                ->whereNull('ended_at')
                ->first();
            if (!$assignment) {
                throw new Exception("Penugasan SPV Entry Anda tidak aktif.");
            }

            $oldStatus = $tracker->status->value;
            $tracker->status = WorkflowStatus::ENTRY_COMPLETED;
            $tracker->completed_at = now();
            $tracker->last_changed_by = $actor->id;
            $tracker->save();

            WorkflowHistory::create([
                'project_id' => $project->id,
                'workflow_step_id' => $tracker->id,
                'from_status' => $oldStatus,
                'to_status' => WorkflowStatus::ENTRY_COMPLETED->value,
                'actor_id' => $actor->id,
                'metadata' => json_encode(['workflow_review_id' => $review->id]),
            ]);

            $review->decision = WorkflowReviewDecision::APPROVED;
            $review->reviewer_id = $actor->id;
            $review->decided_at = now();
            $review->save();

            $spvTask->status = TaskStatus::COMPLETED;
            $spvTask->completed_at = now();
            $spvTask->save();

            $entryTask->status = TaskStatus::COMPLETED;
            $entryTask->completed_at = now();
            $entryTask->save();

            $workflowA->status = WorkflowStatus::COMPLETE;
            $workflowA->completed_at = now();
            $workflowA->last_changed_by = $actor->id;
            $workflowA->save();

            activity()
                ->performedOn($spvTask)
                ->causedBy($actor)
                ->event('approved')
                ->log("Menyetujui Hasil Entry SIHALAL");

            app(\App\Modules\Workflows\Services\SlaManagerService::class)->completeCycle($spvTask);
        });
        
        event(new \App\Events\WorkflowACompleted($spvTask->project_id));
    }

    public function requestRevision(Task $spvTask, User $actor, string $reason): void
    {
        DB::transaction(function () use ($spvTask, $actor, $reason) {
            if (empty(trim($reason))) {
                throw new Exception("Alasan revisi wajib diisi.");
            }

            $project = Project::where('id', $spvTask->project_id)->lockForUpdate()->firstOrFail();
            $spvTask = Task::where('id', $spvTask->id)->lockForUpdate()->firstOrFail();
            $entryTask = Task::where('id', $spvTask->parent_task_id)->lockForUpdate()->firstOrFail();
            
            $review = WorkflowReview::where('review_task_id', $spvTask->id)->lockForUpdate()->firstOrFail();
            $tracker = WorkflowStep::where('id', $review->workflow_step_id)->lockForUpdate()->firstOrFail();

            if ($spvTask->status !== TaskStatus::IN_PROGRESS) {
                throw new Exception("Task Review belum dimulai atau sudah selesai.");
            }
            if ($review->decision === WorkflowReviewDecision::REVISION_REQUESTED) {
                return; // Idempotent
            }
            if ($review->decision !== WorkflowReviewDecision::PENDING) {
                throw new Exception("Review telah diputuskan.");
            }

            $assignment = $project->assignments()
                ->where('assignment_role', AssignmentRole::SPV_ENTRY->value)
                ->where('user_id', $actor->id)
                ->whereNull('ended_at')
                ->first();
            if (!$assignment) {
                throw new Exception("Penugasan SPV Entry Anda tidak aktif.");
            }

            $oldStatus = $tracker->status->value;
            $tracker->status = WorkflowStatus::DOCUMENT_REVISION;
            $tracker->last_changed_by = $actor->id;
            $tracker->save();

            WorkflowHistory::create([
                'project_id' => $project->id,
                'workflow_step_id' => $tracker->id,
                'from_status' => $oldStatus,
                'to_status' => WorkflowStatus::DOCUMENT_REVISION->value,
                'actor_id' => $actor->id,
                'reason' => $reason,
                'metadata' => json_encode(['workflow_review_id' => $review->id]),
            ]);

            $review->decision = WorkflowReviewDecision::REVISION_REQUESTED;
            $review->reviewer_id = $actor->id;
            $review->reason = $reason;
            $review->decided_at = now();
            $review->save();

            $spvTask->status = TaskStatus::COMPLETED;
            $spvTask->completed_at = now();
            $spvTask->save();

            $entryTask->status = TaskStatus::REVISION;
            $entryTask->save();

            $slaManager = app(\App\Modules\Workflows\Services\SlaManagerService::class);
            $slaManager->completeCycle($spvTask);
            $slaManager->newCycle($entryTask);

            activity()
                ->performedOn($spvTask)
                ->causedBy($actor)
                ->event('revision_requested')
                ->log("Meminta Revisi Entry SIHALAL");

            // Notification to Entry
            if ($entryTask->assignee) {
                \Filament\Notifications\Notification::make()
                    ->title('Revisi Entry SIHALAL')
                    ->body("Terdapat revisi dari SPV Entry untuk Project {$project->project_name}: {$reason}")
                    ->warning()
                    ->sendToDatabase($entryTask->assignee);
            }
        });
    }
}
