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

class AuditorReviewService
{
    public function startReview(Task $auditorTask, User $actor): void
    {
        DB::transaction(function () use ($auditorTask, $actor) {
            $auditorTask = Task::where('id', $auditorTask->id)->lockForUpdate()->firstOrFail();
            $entryTask = Task::where('id', $auditorTask->parent_task_id)->lockForUpdate()->firstOrFail();
            
            $review = WorkflowReview::where('review_task_id', $auditorTask->id)->lockForUpdate()->firstOrFail();
            $tracker = WorkflowStep::where('id', $review->workflow_step_id)->lockForUpdate()->firstOrFail();
            $project = Project::where('id', $auditorTask->project_id)->firstOrFail();

            if ($project->status !== ProjectStatus::OPERATIONAL) {
                throw new Exception("Review tidak dapat dimulai karena project tidak dalam status operasional.");
            }

            if ($auditorTask->status !== TaskStatus::TODO) {
                throw new Exception("Review ini sudah dimulai atau telah selesai.");
            }

            if ($auditorTask->assigned_to !== $actor->id) {
                throw new Exception("Anda tidak memiliki akses untuk mereview task ini.");
            }

            $auditorTask->status = TaskStatus::IN_PROGRESS;
            $auditorTask->save();

            $oldStatus = $tracker->status->value;
            $tracker->status = WorkflowStatus::DOCUMENT_REVIEW;
            $tracker->last_changed_by = $actor->id;
            $tracker->save();

            WorkflowHistory::create([
                'project_id' => $project->id,
                'workflow_step_id' => $tracker->id,
                'from_status' => $oldStatus,
                'to_status' => WorkflowStatus::DOCUMENT_REVIEW->value,
                'actor_id' => $actor->id,
            ]);

            activity()
                ->performedOn($auditorTask)
                ->causedBy($actor)
                ->event('started')
                ->log("Mulai melakukan review hasil pendampingan audit");
        });
    }

    public function approveExecution(Task $auditorTask, User $actor): void
    {
        DB::transaction(function () use ($auditorTask, $actor) {
            $project = Project::where('id', $auditorTask->project_id)->lockForUpdate()->firstOrFail();
            $auditorTask = Task::where('id', $auditorTask->id)->lockForUpdate()->firstOrFail();
            $entryTask = Task::where('id', $auditorTask->parent_task_id)->lockForUpdate()->firstOrFail();
            
            $review = WorkflowReview::where('review_task_id', $auditorTask->id)->lockForUpdate()->firstOrFail();
            $tracker = WorkflowStep::where('id', $review->workflow_step_id)->lockForUpdate()->firstOrFail();

            if ($auditorTask->status !== TaskStatus::IN_PROGRESS) {
                throw new Exception("Task Review belum dimulai atau sudah selesai.");
            }

            if ($auditorTask->assigned_to !== $actor->id) {
                throw new Exception("Anda tidak memiliki akses untuk menyetujui review ini.");
            }

            $review->decision = WorkflowReviewDecision::APPROVED->value;
            $review->decided_at = now();
            $review->save();

            $auditorTask->status = TaskStatus::COMPLETED;
            $auditorTask->completed_at = now();
            $auditorTask->save();

            $entryTask->status = TaskStatus::COMPLETED;
            $entryTask->completed_at = now();
            $entryTask->save();

            $oldStatus = $tracker->status->value;
            $tracker->status = WorkflowStatus::FIELD_AUDIT_COMPLETED;
            $tracker->last_changed_by = $actor->id;
            $tracker->save();

            WorkflowHistory::create([
                'project_id' => $project->id,
                'workflow_step_id' => $tracker->id,
                'from_status' => $oldStatus,
                'to_status' => WorkflowStatus::FIELD_AUDIT_COMPLETED->value,
                'actor_id' => $actor->id,
            ]);

            activity()
                ->performedOn($auditorTask)
                ->causedBy($actor)
                ->event('approved')
                ->log("Menyetujui hasil Pelaksanaan Audit");

            app(\App\Modules\Workflows\Services\SlaManagerService::class)->completeCycle($auditorTask);
        });
    }

    public function updateAuditorStatus(Project $project, User $actor, WorkflowStatus $status, ?string $reason = null): void
    {
        DB::transaction(function () use ($project, $actor, $status, $reason) {
            $tracker = WorkflowStep::where('project_id', $project->id)
                ->where('step_code', 'AUDITOR_PROGRESS')
                ->lockForUpdate()
                ->firstOrFail();

            $oldStatus = $tracker->status->value;

            // Jika status lama sudah AUDIT_REPORT_COMPLETED atau di atasnya,
            // maka status tidak boleh diturunkan melalui update biasa.
            $finalStatuses = [
                WorkflowStatus::AUDIT_REPORT_COMPLETED->value,
                WorkflowStatus::WAITING_FATWA_SESSION->value,
                WorkflowStatus::FATWA_SESSION_COMPLETED->value,
            ];

            if (in_array($oldStatus, $finalStatuses) && !in_array($status->value, $finalStatuses)) {
                throw new \Exception("Status Auditor sudah mencapai batas final, tidak dapat diturunkan. Gunakan fitur Buka Kembali Workflow.");
            }

            $tracker->status = $status;
            $tracker->last_changed_by = $actor->id;
            $tracker->save();

            \App\Modules\Workflows\Models\WorkflowHistory::create([
                'project_id' => $project->id,
                'workflow_step_id' => $tracker->id,
                'from_status' => $oldStatus,
                'to_status' => $status->value,
                'actor_id' => $actor->id,
            ]);

            activity()
                ->performedOn($project)
                ->causedBy($actor)
                ->event('auditor_status_updated')
                ->withProperties(['reason' => $reason, 'status' => $status->value])
                ->log("Status Auditor diperbarui menjadi {$status->getLabel()}");

            if ($status === WorkflowStatus::AUDIT_REPORT_COMPLETED) {
                event(new \App\Events\WorkflowBCompleted($project->id));
            }
        });
    }

    public function reviewFinding(\App\Modules\Workflows\Models\AuditFinding $finding, User $actor, \App\Modules\Workflows\Enums\AuditFindingStatus $status, array $data): void
    {
        DB::transaction(function () use ($finding, $actor, $status, $data) {
            $finding = \App\Modules\Workflows\Models\AuditFinding::where('id', $finding->id)->lockForUpdate()->firstOrFail();
            $finding->status = $status;
            
            if ($status === \App\Modules\Workflows\Enums\AuditFindingStatus::CORRECTION_REQUIRED) {
                $finding->resolution_notes = $data['resolution_notes'] ?? null;
                $finding->evidence_required = $data['evidence_required'] ?? true;
                // Owner correction if needed, but wait it's not in migration
            }
            
            $finding->resolved_by = $actor->id;
            $finding->resolved_at = now();
            $finding->save();

            activity()
                ->performedOn($finding)
                ->causedBy($actor)
                ->event('finding_reviewed')
                ->withProperties(['status' => $status->value, 'notes' => $finding->resolution_notes])
                ->log("Mereview temuan audit menjadi {$status->getLabel()}");
        });
    }

    public function requestRevision(Task $auditorTask, User $actor, string $reason): void
    {
        DB::transaction(function () use ($auditorTask, $actor, $reason) {
            if (empty(trim($reason))) {
                throw new Exception("Alasan revisi wajib diisi.");
            }

            $project = Project::where('id', $auditorTask->project_id)->lockForUpdate()->firstOrFail();
            $auditorTask = Task::where('id', $auditorTask->id)->lockForUpdate()->firstOrFail();
            $entryTask = Task::where('id', $auditorTask->parent_task_id)->lockForUpdate()->firstOrFail();
            
            $review = WorkflowReview::where('review_task_id', $auditorTask->id)->lockForUpdate()->firstOrFail();
            $tracker = WorkflowStep::where('id', $review->workflow_step_id)->lockForUpdate()->firstOrFail();

            if ($auditorTask->status !== TaskStatus::IN_PROGRESS) {
                throw new Exception("Task Review belum dimulai atau sudah selesai.");
            }

            if ($auditorTask->assigned_to !== $actor->id) {
                throw new Exception("Anda tidak memiliki akses untuk menolak review ini.");
            }

            $review->decision = WorkflowReviewDecision::REVISION_REQUESTED->value;
            $review->reason = $reason;
            $review->decided_at = now();
            $review->save();

            $auditorTask->status = TaskStatus::COMPLETED;
            $auditorTask->completed_at = now();
            $auditorTask->save();

            $entryTask->status = TaskStatus::IN_PROGRESS;
            $entryTask->save();

            $slaManager = app(\App\Modules\Workflows\Services\SlaManagerService::class);
            $slaManager->completeCycle($auditorTask);
            $slaManager->newCycle($entryTask);

            $execution = \App\Modules\Workflows\Models\AuditExecution::where('task_id', $entryTask->id)->lockForUpdate()->firstOrFail();
            $execution->submitted_at = null;
            $execution->submitted_by = null;
            $execution->save();

            $companionTracker = WorkflowStep::where('project_id', $project->id)
                ->where('step_code', 'COMPANION_PROGRESS')
                ->lockForUpdate()
                ->firstOrFail();

            $oldCompanionStatus = $companionTracker->status->value;
            $companionTracker->status = WorkflowStatus::WAITING_CLIENT_CORRECTION;
            $companionTracker->last_changed_by = $actor->id;
            $companionTracker->save();

            WorkflowHistory::create([
                'project_id' => $project->id,
                'workflow_step_id' => $companionTracker->id,
                'from_status' => $oldCompanionStatus,
                'to_status' => WorkflowStatus::WAITING_CLIENT_CORRECTION->value,
                'actor_id' => $actor->id,
            ]);

            $oldStatus = $tracker->status->value;
            $tracker->status = WorkflowStatus::NONCONFORMITY_FOUND;
            $tracker->last_changed_by = $actor->id;
            $tracker->save();

            WorkflowHistory::create([
                'project_id' => $project->id,
                'workflow_step_id' => $tracker->id,
                'from_status' => $oldStatus,
                'to_status' => WorkflowStatus::NONCONFORMITY_FOUND->value,
                'actor_id' => $actor->id,
            ]);

            activity()
                ->performedOn($auditorTask)
                ->causedBy($actor)
                ->event('revision_requested')
                ->withProperties(['reason' => $reason])
                ->log("Meminta revisi hasil Pelaksanaan Audit: {$reason}");
        });
    }
}
