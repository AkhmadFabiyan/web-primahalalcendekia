<?php

namespace App\Modules\Workflows\Services;

use App\Models\User;
use App\Modules\Projects\Enums\AssignmentRole;
use App\Modules\Projects\Models\Project;
use App\Modules\Workflows\Enums\TaskPriority;
use App\Modules\Workflows\Enums\TaskStatus;
use App\Modules\Workflows\Enums\TaskType;
use App\Modules\Workflows\Models\Task;
use App\Modules\Workflows\Models\TaskAssignmentHistory;

class TaskService
{
    public function ensureInitialOperationalTask(Project $project, User $admin): ?Task
    {
        $taskKey = "PROJECT-{$project->id}:INITIAL_DOCUMENT_COMPLETION";

        if (Task::where('project_id', $project->id)->where('task_key', $taskKey)->exists()) {
            return null; // Already created
        }

        $now = now();

        $task = Task::create([
            'project_id' => $project->id,
            'assigned_to' => $admin->id,
            'assignment_role' => AssignmentRole::ADMIN->value,
            'task_type' => TaskType::DOCUMENT_COMPLETION->value,
            'task_key' => $taskKey,
            'title' => 'Lengkapi Dokumen Persyaratan Klien',
            'description' => 'Silakan periksa dan lengkapi dokumen persyaratan sertifikasi halal klien.',
            'priority' => TaskPriority::MEDIUM->value,
            'status' => TaskStatus::TODO->value,
            'entered_at' => $now,
            // Deadline omitted for this basic setup, can be set based on SLA if required
        ]);

        TaskAssignmentHistory::create([
            'task_id' => $task->id,
            'from_user_id' => null,
            'to_user_id' => $admin->id,
            'changed_by' => auth()->id() ?? $admin->id,
            'entered_at' => $now,
            'reason' => 'Task awal dibuat otomatis',
        ]);

        app(\App\Modules\Workflows\Services\SlaManagerService::class)->startCycle($task);

        return $task;
    }

    public function completeInitialTask(Project $project, ?User $actor, string $reason): void
    {
        $taskKey = "PROJECT-{$project->id}:INITIAL_DOCUMENT_COMPLETION";
        
        $task = Task::where('project_id', $project->id)
            ->where('task_key', $taskKey)
            ->where('status', '!=', TaskStatus::COMPLETED->value)
            ->first();

        if ($task) {
            $task->update([
                'status' => TaskStatus::COMPLETED->value,
                'completed_at' => now(),
            ]);

            activity()
                ->performedOn($task)
                ->causedBy($actor)
                ->event('completed')
                ->log($reason);

            app(\App\Modules\Workflows\Services\SlaManagerService::class)->completeCycle($task);
        }
    }
}
