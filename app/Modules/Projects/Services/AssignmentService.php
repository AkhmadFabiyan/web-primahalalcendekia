<?php

namespace App\Modules\Projects\Services;

use App\Models\User;
use App\Modules\Projects\Enums\AssignmentRole;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\ProjectAssignment;
use App\Modules\Workflows\Enums\TaskStatus;
use App\Modules\Workflows\Models\Task;
use App\Modules\Workflows\Models\TaskAssignmentHistory;
use App\Modules\Workflows\Services\TaskService;
use Exception;
use Illuminate\Support\Facades\DB;

class AssignmentService
{
    public function __construct(private TaskService $taskService)
    {
    }

    /**
     * Reassign or assign a new user to a role in the project.
     */
    public function reassign(Project $project, AssignmentRole $role, User $newUser, ?string $reason = null): void
    {
        DB::transaction(function () use ($project, $role, $newUser, $reason) {
            $project = Project::lockForUpdate()->find($project->id);

            // Validasi User baru aktif dan internal
            // Asumsi: is_active and is_internal are available or handled by the caller/validation

            $now = now();
            $actorId = auth()->id();

            // Tutup assignment lama jika ada
            $oldAssignment = ProjectAssignment::where('project_id', $project->id)
                ->where('assignment_role', $role->value)
                ->whereNull('ended_at')
                ->first();

            $fromUserId = null;
            if ($oldAssignment) {
                if ($oldAssignment->user_id === $newUser->id) {
                    return; // No change
                }
                $oldAssignment->update(['ended_at' => $now]);
                $fromUserId = $oldAssignment->user_id;
            }

            // Buat assignment baru
            ProjectAssignment::create([
                'project_id' => $project->id,
                'user_id' => $newUser->id,
                'assignment_role' => $role->value,
                'assigned_by' => $actorId,
                'assigned_at' => $now,
            ]);

            // Reassign tasks if there is an old assignment
            if ($fromUserId) {
                $unfinishedTasks = Task::where('project_id', $project->id)
                    ->where('assignment_role', $role->value)
                    ->where('status', '!=', TaskStatus::COMPLETED->value)
                    ->get();

                foreach ($unfinishedTasks as $task) {
                    if (in_array($task->status->value, [TaskStatus::IN_PROGRESS->value, TaskStatus::WAITING_REVIEW->value, TaskStatus::REVISION->value]) && empty($reason)) {
                        throw new Exception("Alasan wajib diisi jika Task sudah dalam proses.");
                    }

                    // Tutup histori lama
                    $lastHistory = TaskAssignmentHistory::where('task_id', $task->id)
                        ->whereNull('ended_at')
                        ->first();
                    
                    if ($lastHistory) {
                        $lastHistory->update(['ended_at' => $now]);
                    }

                    // Buat histori baru
                    TaskAssignmentHistory::create([
                        'task_id' => $task->id,
                        'from_user_id' => $fromUserId,
                        'to_user_id' => $newUser->id,
                        'changed_by' => $actorId,
                        'reason' => $reason,
                        'entered_at' => $now,
                    ]);

                    // Perbarui task
                    $task->update([
                        'assigned_to' => $newUser->id,
                        'entered_at' => $now,
                    ]);
                }
            }

            // Jika project sudah ACTIVE dan Admin ditugaskan, pastikan task awal ada
            if ($project->status->value === \App\Modules\Projects\Enums\ProjectStatus::ACTIVE->value && $role === AssignmentRole::ADMIN) {
                $this->taskService->ensureInitialOperationalTask($project, $newUser);
            }

            // TODO: Tulis Activity Log
            // TODO: Kirim Notifikasi
        });
    }
}
