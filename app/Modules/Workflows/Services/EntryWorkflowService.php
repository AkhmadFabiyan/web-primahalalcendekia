<?php

namespace App\Modules\Workflows\Services;

use App\Models\User;
use App\Modules\Projects\Enums\AssignmentRole;
use App\Modules\Projects\Enums\ProjectStatus;
use App\Modules\Projects\Models\Project;
use App\Modules\Workflows\Enums\TaskStatus;
use App\Modules\Workflows\Enums\TaskType;
use App\Modules\Workflows\Enums\WorkflowNoteType;
use App\Modules\Workflows\Enums\WorkflowStatus;
use App\Modules\Workflows\Models\Task;
use App\Modules\Workflows\Models\TaskChecklistItem;
use App\Modules\Workflows\Models\WorkflowHistory;
use App\Modules\Workflows\Models\WorkflowNote;
use App\Modules\Workflows\Models\WorkflowStep;
use Exception;
use Illuminate\Support\Facades\DB;

class EntryWorkflowService
{
    public function startEntry(Task $task, User $actor): void
    {
        DB::transaction(function () use ($task, $actor) {
            $project = Project::where('id', $task->project_id)->lockForUpdate()->firstOrFail();
            $task = Task::where('id', $task->id)->lockForUpdate()->firstOrFail();
            
            $tracker = WorkflowStep::where('project_id', $project->id)
                ->where('step_code', 'ENTRY_PROGRESS')
                ->lockForUpdate()
                ->first();

            // 4. Validasi User adalah assigned_to
            if ($task->assigned_to !== $actor->id) {
                throw new Exception("Anda tidak memiliki akses untuk memulai tugas ini.");
            }

            // 5. Validasi Assignment Entry masih aktif
            $assignment = $project->assignments()
                ->where('assignment_role', AssignmentRole::ENTRY->value)
                ->where('user_id', $actor->id)
                ->whereNull('ended_at')
                ->first();
            if (!$assignment) {
                throw new Exception("Penugasan Entry Anda sudah tidak aktif.");
            }

            // 6. Validasi User aktif dan memiliki role Entry
            if ($actor->status !== 'ACTIVE' || !$actor->hasRole('Entry')) {
                throw new Exception("Akun Anda tidak aktif atau tidak memiliki role Entry.");
            }

            // 7. Validasi Task masih TODO
            if ($task->status !== TaskStatus::TODO) {
                throw new Exception("Tugas tidak dapat dimulai karena status saat ini adalah " . $task->status->value);
            }

            // 8. Pastikan dokumen masih lengkap dan tidak memiliki revisi terbuka
            $docStep = WorkflowStep::where('project_id', $project->id)
                ->where('step_code', 'DOCUMENT_ADMINISTRATION')
                ->first();
            if (!$docStep || $docStep->status !== WorkflowStatus::COMPLETE) {
                throw new Exception("Dokumen belum lengkap atau masih terdapat revisi yang terbuka.");
            }

            // 9. Pastikan kredensial SIHALAL tersedia
            if (!$project->sihalalCredential) {
                throw new Exception("Kredensial SIHALAL belum tersedia.");
            }

            // 10 & 11. Ubah Task menjadi IN_PROGRESS dan isi started_at
            $task->status = TaskStatus::IN_PROGRESS;
            if (!$task->started_at) {
                $task->started_at = now();

                // Generate Checklist Snapshot
                $checklists = [
                    ['code' => 'DOC_CHECK', 'label' => 'Dokumen telah diperiksa', 'sort_order' => 1],
                    ['code' => 'LOGIN_SUCCESS', 'label' => 'Login SIHALAL berhasil', 'sort_order' => 2],
                    ['code' => 'COMPANY_DATA', 'label' => 'Data perusahaan telah diinput', 'sort_order' => 3],
                    ['code' => 'PRODUCT_DATA', 'label' => 'Data produk telah diinput', 'sort_order' => 4],
                    ['code' => 'MATERIAL_DATA', 'label' => 'Data bahan telah diinput', 'sort_order' => 5],
                    ['code' => 'FACILITY_DATA', 'label' => 'Data fasilitas telah diinput', 'sort_order' => 6],
                    ['code' => 'SUPERVISOR_DATA', 'label' => 'Data penyelia halal telah diinput', 'sort_order' => 7],
                    ['code' => 'DATA_VERIFIED', 'label' => 'Seluruh data telah diverifikasi', 'sort_order' => 8],
                    ['code' => 'SUPPORTING_DOCS', 'label' => 'Dokumen pendukung telah diunggah ke SIHALAL', 'sort_order' => 9],
                ];

                foreach ($checklists as $item) {
                    TaskChecklistItem::create(array_merge($item, [
                        'task_id' => $task->id,
                        'is_required' => true,
                        'is_completed' => false,
                    ]));
                }
            }
            $task->save();

            // 12 & 13. Jika Project ACTIVE, ubah menjadi OPERATIONAL
            if ($project->status === ProjectStatus::ACTIVE) {
                $project->status = ProjectStatus::OPERATIONAL;
                $project->save();
            }

            activity()
                ->performedOn($task)
                ->causedBy($actor)
                ->event('started')
                ->log("Mulai Mengerjakan Tugas Entry SIHALAL");
        });
    }

    public function updateStatus(Task $task, User $actor, string $newStatusValue, ?string $reason = null, ?string $noteContent = null, ?string $noteType = null): void
    {
        DB::transaction(function () use ($task, $actor, $newStatusValue, $reason, $noteContent, $noteType) {
            $project = Project::where('id', $task->project_id)->lockForUpdate()->firstOrFail();
            $task = Task::where('id', $task->id)->lockForUpdate()->firstOrFail();
            $tracker = WorkflowStep::where('project_id', $project->id)
                ->where('step_code', 'ENTRY_PROGRESS')
                ->lockForUpdate()
                ->firstOrFail();

            if ($task->assigned_to !== $actor->id) {
                throw new Exception("Anda tidak memiliki akses untuk memperbarui status ini.");
            }

            // Allowed Entry Statuses in Phase 17
            $allowedStatuses = [
                WorkflowStatus::ENTRY_NOT_STARTED->value,
                WorkflowStatus::WAITING_CLIENT_DOCUMENTS->value,
                WorkflowStatus::DOCUMENTS_INCOMPLETE->value,
                WorkflowStatus::CREATING_SIHALAL_ACCOUNT->value,
                WorkflowStatus::PREPARING_SJPH_MANUAL->value,
                WorkflowStatus::INPUTTING_MATERIALS_PRODUCTS->value,
            ];

            if (!in_array($newStatusValue, $allowedStatuses)) {
                throw new Exception("Status {$newStatusValue} tidak dapat dipilih secara manual.");
            }

            $oldStatus = $tracker->status->value;

            $finalStatuses = [
                WorkflowStatus::ENTRY_COMPLETED->value,
            ];

            if (in_array($oldStatus, $finalStatuses) && !in_array($newStatusValue, $finalStatuses)) {
                throw new Exception("Status Entry sudah mencapai batas final, tidak dapat diturunkan secara manual. Gunakan fitur Buka Kembali Workflow.");
            }
            
            if ($oldStatus === $newStatusValue && empty($noteContent)) {
                // No change, no note
                return;
            }

            $noteId = null;
            if (!empty($noteContent)) {
                $noteTypeEnum = $noteType ? WorkflowNoteType::tryFrom($noteType) : WorkflowNoteType::WORK_NOTE;
                $note = WorkflowNote::create([
                    'project_id' => $project->id,
                    'workflow_step_id' => $tracker->id,
                    'task_id' => $task->id,
                    'author_id' => $actor->id,
                    'note_type' => $noteTypeEnum?->value ?? WorkflowNoteType::WORK_NOTE->value,
                    'content' => $noteContent,
                    'is_client_visible' => false,
                ]);
                $noteId = $note->id;
            }

            if ($oldStatus !== $newStatusValue) {
                $tracker->status = WorkflowStatus::from($newStatusValue);
                $tracker->last_changed_by = $actor->id;
                $tracker->save();

                WorkflowHistory::create([
                    'project_id' => $project->id,
                    'workflow_step_id' => $tracker->id,
                    'from_status' => $oldStatus,
                    'to_status' => $newStatusValue,
                    'actor_id' => $actor->id,
                    'reason' => $reason,
                    'metadata' => $noteId ? json_encode(['note_id' => $noteId]) : null,
                ]);
            }
        });
    }

    public function addNote(Task $task, User $actor, string $type, string $content, bool $isClientVisible = false): void
    {
        DB::transaction(function () use ($task, $actor, $type, $content, $isClientVisible) {
            $task = Task::where('id', $task->id)->lockForUpdate()->firstOrFail();
            $tracker = WorkflowStep::where('project_id', $task->project_id)
                ->where('step_code', 'ENTRY_PROGRESS')
                ->first();

            WorkflowNote::create([
                'project_id' => $task->project_id,
                'workflow_step_id' => $tracker?->id,
                'task_id' => $task->id,
                'author_id' => $actor->id,
                'note_type' => $type,
                'content' => $content,
                'is_client_visible' => $isClientVisible,
            ]);
        });
    }

    public function submitForReview(Task $task, User $actor): void
    {
        DB::transaction(function () use ($task, $actor) {
            $project = Project::where('id', $task->project_id)->lockForUpdate()->firstOrFail();
            $task = Task::where('id', $task->id)->lockForUpdate()->firstOrFail();
            $tracker = WorkflowStep::where('project_id', $project->id)
                ->where('step_code', 'ENTRY_PROGRESS')
                ->lockForUpdate()
                ->firstOrFail();

            // Assignment SPV
            $spvAssignment = $project->assignments()
                ->where('assignment_role', AssignmentRole::SPV_ENTRY->value)
                ->whereNull('ended_at')
                ->lockForUpdate()
                ->first();

            if ($task->status !== TaskStatus::IN_PROGRESS && $task->status !== TaskStatus::REVISION) {
                throw new Exception("Tugas belum dikerjakan atau sudah di-submit.");
            }
            if ($project->status !== ProjectStatus::OPERATIONAL) {
                throw new Exception("Project tidak dalam status OPERATIONAL.");
            }
            if ($task->assigned_to !== $actor->id) {
                throw new Exception("Anda tidak memiliki akses untuk submit tugas ini.");
            }
            
            $docStep = WorkflowStep::where('project_id', $project->id)
                ->where('step_code', 'DOCUMENT_ADMINISTRATION')
                ->first();
            if (!$docStep || $docStep->status !== WorkflowStatus::COMPLETE) {
                throw new Exception("Dokumen belum lengkap atau masih terdapat revisi yang terbuka.");
            }

            if (!$project->sihalalCredential) {
                throw new Exception("Kredensial SIHALAL belum tersedia.");
            }

            $uncompletedChecklists = TaskChecklistItem::where('task_id', $task->id)
                ->where('is_required', true)
                ->where('is_completed', false)
                ->exists();
            if ($uncompletedChecklists) {
                throw new Exception("Seluruh checklist wajib harus diselesaikan sebelum submit.");
            }

            if ($tracker->status === WorkflowStatus::SUBMITTED_TO_LPH || $tracker->status === WorkflowStatus::ENTRY_COMPLETED) {
                throw new Exception("Entry sudah di-submit.");
            }

            if (!$spvAssignment || !$spvAssignment->user || $spvAssignment->user->status !== 'ACTIVE') {
                throw new Exception("PIC SPV Entry belum ditentukan atau tidak aktif. Hubungi Manager Operasional atau Super Admin.");
            }

            // Ubah tracker ke SUBMITTED_TO_LPH
            $oldStatus = $tracker->status->value;
            $tracker->status = WorkflowStatus::SUBMITTED_TO_LPH;
            $tracker->last_changed_by = $actor->id;
            $tracker->save();

            $history = WorkflowHistory::create([
                'project_id' => $project->id,
                'workflow_step_id' => $tracker->id,
                'from_status' => $oldStatus,
                'to_status' => WorkflowStatus::SUBMITTED_TO_LPH->value,
                'actor_id' => $actor->id,
            ]);

            // Ubah Task Entry menjadi WAITING_REVIEW
            $task->status = TaskStatus::WAITING_REVIEW;
            $task->save();

            app(\App\Modules\Workflows\Services\SlaManagerService::class)->completeCycle($task);

            // Buat Task SPV Entry secara idempotent
            $taskKey = "PROJECT-{$project->id}:SPV_ENTRY_REVIEW:{$history->id}";
            $spvTask = Task::firstOrCreate(
                ['project_id' => $project->id, 'task_key' => $taskKey],
                [
                    'assigned_to' => $spvAssignment->user_id,
                    'assignment_role' => AssignmentRole::SPV_ENTRY->value,
                    'task_type' => 'SPV_ENTRY_REVIEW',
                    'title' => 'Review Hasil Entry SIHALAL',
                    'status' => TaskStatus::TODO,
                    'priority' => $task->priority,
                    'entered_at' => now(),
                    'parent_task_id' => $task->id,
                    'source_workflow_history_id' => $history->id,
                ]
            );

            app(\App\Modules\Workflows\Services\SlaManagerService::class)->startCycle($spvTask);

            // Buat WorkflowReview
            \App\Modules\Workflows\Models\WorkflowReview::firstOrCreate(
                ['submission_history_id' => $history->id],
                [
                    'project_id' => $project->id,
                    'workflow_step_id' => $tracker->id,
                    'entry_task_id' => $task->id,
                    'review_task_id' => $spvTask->id,
                    'decision' => \App\Modules\Workflows\Enums\WorkflowReviewDecision::PENDING->value,
                ]
            );

            activity()
                ->performedOn($task)
                ->causedBy($actor)
                ->event('submitted')
                ->log("Mengirim Hasil Entry SIHALAL ke SPV");

            // Send notification to SPV
            \Filament\Notifications\Notification::make()
                ->title('Review Entry SIHALAL')
                ->body("Project {$project->project_name} telah disubmit oleh Entry dan siap untuk direview.")
                ->success()
                ->sendToDatabase($spvAssignment->user);
        });
    }
}
