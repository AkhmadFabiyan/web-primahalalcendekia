<?php

namespace App\Modules\Workflows\Services;

use App\Models\User;
use App\Modules\Projects\Enums\AssignmentRole;
use App\Modules\Projects\Enums\ProjectStatus;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\ProjectAssignment;
use App\Modules\Workflows\Enums\AuditFindingStatus;
use App\Modules\Workflows\Enums\TaskStatus;
use App\Modules\Workflows\Enums\TaskType;
use App\Modules\Workflows\Enums\WorkflowStatus;
use App\Modules\Workflows\Models\AuditExecution;
use App\Modules\Workflows\Models\AuditFinding;
use App\Modules\Workflows\Models\AuditPlan;
use App\Modules\Workflows\Models\ChecklistTemplate;
use App\Modules\Workflows\Models\Task;
use App\Modules\Workflows\Models\TaskChecklistItem;
use App\Modules\Workflows\Models\WorkflowHistory;
use App\Modules\Workflows\Models\WorkflowReview;
use App\Modules\Workflows\Models\WorkflowStep;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuditExecutionService
{
    public function startExecution(Task $task, User $actor): void
    {
        DB::transaction(function () use ($task, $actor) {
            $project = Project::where('id', $task->project_id)->lockForUpdate()->firstOrFail();
            $task = Task::where('id', $task->id)->lockForUpdate()->firstOrFail();
            $plan = AuditPlan::where('project_id', $project->id)->lockForUpdate()->firstOrFail();
            $tracker = WorkflowStep::where('project_id', $project->id)
                ->where('step_code', 'COMPANION_PROGRESS')
                ->lockForUpdate()
                ->firstOrFail();

            if ($task->task_type !== TaskType::AUDIT_EXECUTION) {
                throw new Exception('Tugas bukan eksekusi audit.');
            }

            if ($task->assigned_to !== $actor->id) {
                throw new Exception('Anda tidak memiliki akses untuk tugas ini.');
            }

            if ($task->status !== TaskStatus::TODO) {
                throw new Exception('Pelaksanaan audit sudah dimulai.');
            }

            if ($project->status !== ProjectStatus::ACTIVE && $project->status !== ProjectStatus::OPERATIONAL) {
                throw new Exception('Project tidak dalam status aktif atau operasional.');
            }

            if (! $plan->confirmed_at) {
                throw new Exception('Rencana audit belum dikonfirmasi.');
            }

            $primaryAuditor = ProjectAssignment::where('project_id', $project->id)
                ->where('assignment_role', AssignmentRole::AUDITOR->value)
                ->where('is_primary', true)
                ->whereNull('ended_at')
                ->first();

            if (! $primaryAuditor) {
                throw new Exception('Auditor Utama belum ditentukan atau tidak aktif.');
            }

            $execution = AuditExecution::firstOrCreate(
                [
                    'audit_plan_id' => $plan->id,
                    'task_id' => $task->id,
                ],
                [
                    'project_id' => $project->id,
                    'started_by' => $actor->id,
                    'started_at' => now(),
                ]
            );

            // Salin checklist jika belum ada
            if (TaskChecklistItem::where('task_id', $task->id)->count() === 0) {
                $templateCode = $plan->audit_method->value === 'ONLINE'
                    ? 'AUDIT_EXECUTION_ONLINE'
                    : 'AUDIT_EXECUTION_ONSITE';

                $template = ChecklistTemplate::where('code', $templateCode)->with('items')->first();
                if ($template) {
                    foreach ($template->items as $item) {
                        TaskChecklistItem::create([
                            'task_id' => $task->id,
                            'code' => $item->code,
                            'label' => $item->label,
                            'is_required' => $item->is_required,
                            'sort_order' => $item->sort_order,
                            'is_completed' => false,
                        ]);
                    }
                }
            }

            $task->status = TaskStatus::IN_PROGRESS;
            $task->started_at = $task->started_at ?? now();
            $task->save();

            if ($tracker->status === WorkflowStatus::AUDIT_SCHEDULED) {
                $oldStatus = $tracker->status->value;
                $tracker->status = WorkflowStatus::AUDIT_IN_PROGRESS;
                $tracker->last_changed_by = $actor->id;
                $tracker->save();

                WorkflowHistory::create([
                    'project_id' => $project->id,
                    'workflow_step_id' => $tracker->id,
                    'from_status' => $oldStatus,
                    'to_status' => WorkflowStatus::AUDIT_IN_PROGRESS->value,
                    'actor_id' => $actor->id,
                ]);
            }

            if ($project->status === ProjectStatus::ACTIVE) {
                $project->status = ProjectStatus::OPERATIONAL;
                $project->save();
            }

            activity()
                ->performedOn($execution)
                ->causedBy($actor)
                ->event('started')
                ->log('Memulai Pelaksanaan Audit');
        });
    }

    public function updateCompanionStatus(Project $project, User $actor, WorkflowStatus $status, ?string $reason = null): void
    {
        DB::transaction(function () use ($project, $actor, $status, $reason) {
            $tracker = WorkflowStep::where('project_id', $project->id)
                ->where('step_code', 'COMPANION_PROGRESS')
                ->lockForUpdate()
                ->firstOrFail();

            $assignment = $project->assignments()
                ->where('assignment_role', AssignmentRole::PENDAMPING_AUDITOR->value)
                ->where('user_id', $actor->id)
                ->whereNull('ended_at')
                ->first();

            if (! $assignment) {
                throw new Exception('Anda bukan Pendamping aktif untuk Project ini.');
            }

            $allowedStatuses = [
                WorkflowStatus::AUDIT_IN_PROGRESS,
                WorkflowStatus::FIELD_EVIDENCE_INCOMPLETE,
                WorkflowStatus::AUDIT_COMPLETED,
                WorkflowStatus::WAITING_CLIENT_CORRECTION,
            ];

            if (! in_array($status, $allowedStatuses)) {
                throw new Exception('Status tidak valid untuk tahap ini.');
            }

            $statusOrder = [
                WorkflowStatus::COMPANION_NOT_PROCESSED->value => 1,
                WorkflowStatus::WAITING_AUDIT_SCHEDULE->value => 2,
                WorkflowStatus::AUDIT_PREPARATION->value => 3,
                WorkflowStatus::FIELD_EVIDENCE_INCOMPLETE->value => 4,
                WorkflowStatus::AUDIT_SCHEDULED->value => 5,
                WorkflowStatus::AUDIT_IN_PROGRESS->value => 6,
                WorkflowStatus::AUDIT_COMPLETED->value => 7,
                WorkflowStatus::WAITING_CLIENT_CORRECTION->value => 8,
                WorkflowStatus::ASSISTANCE_COMPLETED->value => 9,
            ];

            $isDowngrade = ($statusOrder[$status->value] ?? 0) < ($statusOrder[$tracker->status->value] ?? 0);

            if ($isDowngrade && empty($reason)) {
                throw new Exception('Alasan wajib diisi saat menurunkan status pendampingan.');
            }

            $oldStatus = $tracker->status->value;
            $tracker->status = $status;
            $tracker->last_changed_by = $actor->id;
            $tracker->save();

            WorkflowHistory::create([
                'project_id' => $project->id,
                'workflow_step_id' => $tracker->id,
                'from_status' => $oldStatus,
                'to_status' => $status->value,
                'actor_id' => $actor->id,
                'reason' => $reason,
            ]);

            activity()
                ->performedOn($tracker)
                ->causedBy($actor)
                ->event('status_updated')
                ->withProperties([
                    'from' => $oldStatus,
                    'to' => $status->value,
                    'reason' => $reason,
                ])
                ->log('Memperbarui Status Pendamping');
        });
    }

    public function addFinding(Task $task, User $actor, array $data): AuditFinding
    {
        return DB::transaction(function () use ($task, $actor, $data) {
            $execution = AuditExecution::where('task_id', $task->id)->lockForUpdate()->firstOrFail();
            $project = Project::where('id', $task->project_id)->firstOrFail();

            if ($task->assigned_to !== $actor->id) {
                throw new Exception('Anda tidak berhak menambah temuan.');
            }

            if (! in_array($task->status, [TaskStatus::IN_PROGRESS, TaskStatus::REVISION])) {
                throw new Exception('Task harus dalam status IN_PROGRESS atau REVISION.');
            }

            if ($project->status === ProjectStatus::CANCELLED) {
                throw new Exception('Project telah dibatalkan.');
            }

            if ($execution->submitted_at) {
                throw new Exception('Hasil audit telah dikunci (submitted).');
            }

            $count = AuditFinding::where('audit_execution_id', $execution->id)->lockForUpdate()->count();
            $number = 'FIND-'.str_pad($count + 1, 3, '0', STR_PAD_LEFT);

            return AuditFinding::create([
                'audit_execution_id' => $execution->id,
                'project_id' => $project->id,
                'finding_number' => $number,
                'description' => $data['description'],
                'evidence_required' => $data['evidence_required'] ?? false,
                'status' => AuditFindingStatus::OPEN->value,
                'reported_by' => $actor->id,
                'reported_at' => now(),
            ]);
        });
    }

    public function updateFinding(AuditFinding $finding, User $actor, array $data): void
    {
        DB::transaction(function () use ($finding, $actor, $data) {
            $finding = AuditFinding::where('id', $finding->id)->lockForUpdate()->firstOrFail();
            $execution = AuditExecution::where('id', $finding->audit_execution_id)->firstOrFail();
            $task = Task::where('id', $execution->task_id)->firstOrFail();

            if ($task->assigned_to !== $actor->id) {
                throw new Exception('Anda tidak berhak mengubah temuan.');
            }

            if ($execution->submitted_at) {
                throw new Exception('Hasil audit telah dikunci.');
            }

            $finding->description = $data['description'] ?? $finding->description;
            $finding->evidence_required = $data['evidence_required'] ?? $finding->evidence_required;
            $finding->save();
        });
    }

    public function voidFinding(AuditFinding $finding, User $actor, string $reason): void
    {
        DB::transaction(function () use ($finding, $actor, $reason) {
            $finding = AuditFinding::where('id', $finding->id)->lockForUpdate()->firstOrFail();
            $execution = AuditExecution::where('id', $finding->audit_execution_id)->firstOrFail();
            $task = Task::where('id', $execution->task_id)->firstOrFail();

            if ($task->assigned_to !== $actor->id) {
                throw new Exception('Anda tidak berhak membatalkan temuan.');
            }

            if ($execution->submitted_at) {
                throw new Exception('Hasil audit telah dikunci.');
            }

            if (empty($reason)) {
                throw new Exception('Alasan pembatalan wajib diisi.');
            }

            $finding->status = AuditFindingStatus::VOIDED->value;
            $finding->resolution_notes = $reason;
            $finding->resolved_by = $actor->id;
            $finding->resolved_at = now();
            $finding->save();
        });
    }

    public function attachFindingEvidence(AuditFinding $finding, User $actor, $file): void
    {
        DB::transaction(function () use ($finding, $actor, $file) {
            $finding = AuditFinding::where('id', $finding->id)->lockForUpdate()->firstOrFail();
            $execution = AuditExecution::where('id', $finding->audit_execution_id)->firstOrFail();
            $task = Task::where('id', $execution->task_id)->firstOrFail();

            if ($task->assigned_to !== $actor->id) {
                throw new Exception('Anda tidak berhak menambah bukti temuan.');
            }

            if ($execution->submitted_at) {
                throw new Exception('Hasil audit telah dikunci.');
            }

            $finding->addMedia($file)
                ->toMediaCollection('audit-finding-evidence');
        });
    }

    public function submitToAuditor(Task $task, User $actor, array $data): void
    {
        $notifications = [];

        DB::transaction(function () use ($task, $actor, $data, &$notifications) {
            $project = Project::where('id', $task->project_id)->lockForUpdate()->firstOrFail();
            $task = Task::where('id', $task->id)->lockForUpdate()->firstOrFail();
            $tracker = WorkflowStep::where('project_id', $project->id)
                ->where('step_code', 'COMPANION_PROGRESS')
                ->lockForUpdate()
                ->firstOrFail();
            $auditorTracker = WorkflowStep::where('project_id', $project->id)
                ->whereIn('step_code', ['AUDITOR_PROGRESS', 'AUDITOR_REVIEW'])
                ->lockForUpdate()
                ->firstOrFail();

            $execution = AuditExecution::where('task_id', $task->id)->lockForUpdate()->firstOrFail();

            if ($task->assigned_to !== $actor->id) {
                throw new Exception('Anda tidak memiliki akses submit.');
            }

            if (! in_array($task->status, [TaskStatus::IN_PROGRESS, TaskStatus::REVISION])) {
                throw new Exception('Tugas belum dalam proses eksekusi.');
            }

            if ($project->status === ProjectStatus::CANCELLED || $project->status === ProjectStatus::COMPLETED) {
                throw new Exception('Status Project tidak mengizinkan aksi ini.');
            }

            // Cek kelengkapan
            if (empty($data['summary'])) {
                throw new Exception('Ringkasan audit wajib diisi.');
            }
            if (! isset($data['has_findings'])) {
                throw new Exception('Pernyataan ketersediaan temuan wajib diisi.');
            }

            $uncompletedChecklists = TaskChecklistItem::where('task_id', $task->id)
                ->where('is_required', true)
                ->where('is_completed', false)
                ->exists();

            if ($uncompletedChecklists) {
                throw new Exception('Seluruh checklist pelaksanaan audit wajib diselesaikan.');
            }

            $findings = AuditFinding::where('audit_execution_id', $execution->id)
                ->where('status', '!=', AuditFindingStatus::VOIDED->value)
                ->get();

            if ($data['has_findings']) {
                if ($findings->isEmpty()) {
                    throw new Exception('Anda menyatakan ada temuan, tetapi tidak ada temuan aktif yang dicatat.');
                }
            } else {
                $openFindings = $findings->where('status', AuditFindingStatus::OPEN->value);
                if ($openFindings->isNotEmpty()) {
                    throw new Exception("Terdapat temuan terbuka. Anda tidak dapat memilih opsi 'Tidak ada temuan'.");
                }
            }

            // Validasi bukti untuk temuan yang membutuhkan bukti
            foreach ($findings as $finding) {
                if ($finding->evidence_required) {
                    if ($finding->getMedia('audit-finding-evidence')->isEmpty()) {
                        throw new Exception("Temuan {$finding->finding_number} mewajibkan bukti, tetapi belum ada file yang diunggah.");
                    }
                }
            }

            $primaryAuditor = ProjectAssignment::where('project_id', $project->id)
                ->where('assignment_role', AssignmentRole::AUDITOR->value)
                ->where('is_primary', true)
                ->whereNull('ended_at')
                ->first();

            if (! $primaryAuditor) {
                throw new Exception('Auditor Utama belum ditentukan atau tidak aktif.');
            }

            $execution->summary = $data['summary'];
            $execution->has_findings = $data['has_findings'];
            $execution->submitted_by = $actor->id;
            $execution->submitted_at = now();
            $execution->save();

            $oldStatus = $tracker->status->value;
            $tracker->status = WorkflowStatus::ASSISTANCE_COMPLETED;
            $tracker->last_changed_by = $actor->id;
            $tracker->save();

            $history = WorkflowHistory::create([
                'project_id' => $project->id,
                'workflow_step_id' => $tracker->id,
                'from_status' => $oldStatus,
                'to_status' => WorkflowStatus::ASSISTANCE_COMPLETED->value,
                'actor_id' => $actor->id,
            ]);

            $task->status = TaskStatus::WAITING_REVIEW;
            $task->save();

            app(SlaManagerService::class)->completeCycle($task);

            $auditorTaskKey = "PROJECT-{$project->id}:AUDITOR_REVIEW:{$history->id}";
            $auditorTask = Task::firstOrCreate(
                ['project_id' => $project->id, 'task_key' => $auditorTaskKey],
                [
                    'assigned_to' => $primaryAuditor->user_id,
                    'assignment_role' => AssignmentRole::AUDITOR->value,
                    'task_type' => TaskType::AUDITOR_REVIEW->value,
                    'title' => 'Review Hasil Pendampingan Audit',
                    'status' => TaskStatus::TODO,
                    'priority' => 'HIGH',
                    'parent_task_id' => $task->id,
                    'source_workflow_history_id' => $history->id,
                    'entered_at' => now(),
                ]
            );

            app(SlaManagerService::class)->startCycle($auditorTask);

            WorkflowReview::create([
                'id' => Str::uuid(),
                'project_id' => $project->id,
                'workflow_step_id' => $auditorTracker->id,
                'submission_history_id' => $history->id,
                'entry_task_id' => $task->id,
                'review_task_id' => $auditorTask->id,
                'reviewer_id' => $primaryAuditor->user_id,
                'decision' => 'PENDING',
                'started_at' => now(),
            ]);

            // Notify Auditors
            $auditors = ProjectAssignment::where('project_id', $project->id)
                ->where('assignment_role', AssignmentRole::AUDITOR->value)
                ->whereNull('ended_at')
                ->with('user')
                ->get();

            foreach ($auditors as $auditorAssign) {
                if ($auditorAssign->user && $auditorAssign->user->status === 'ACTIVE') {
                    $notifications[] = [
                        'user' => $auditorAssign->user,
                        'project_name' => $project->project_name,
                    ];
                }
            }

            activity()
                ->performedOn($execution)
                ->causedBy($actor)
                ->event('submitted')
                ->log('Menyerahkan hasil pendampingan ke Auditor');
        });

        foreach ($notifications as $notif) {
            Notification::make()
                ->title('Hasil Pendampingan Diserahkan')
                ->body("Hasil audit {$notif['project_name']} telah diserahkan dan menunggu review.")
                ->success()
                ->sendToDatabase($notif['user']);
        }
    }
}
