<?php

namespace App\Modules\Workflows\Models;

use App\Models\User;
use App\Modules\Projects\Enums\AssignmentRole;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Traits\LocksWhenProjectLocked;
use App\Modules\Workflows\Enums\TaskPriority;
use App\Modules\Workflows\Enums\TaskStatus;
use App\Modules\Workflows\Enums\TaskType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasFactory, HasUuids, LocksWhenProjectLocked;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'assignment_role' => AssignmentRole::class,
            'task_type' => TaskType::class,
            'priority' => TaskPriority::class,
            'status' => TaskStatus::class,
            'entered_at' => 'datetime',
            'deadline' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignmentHistories(): HasMany
    {
        return $this->hasMany(TaskAssignmentHistory::class);
    }

    public function workflowNotes(): HasMany
    {
        return $this->hasMany(WorkflowNote::class);
    }

    public function checklistItems(): HasMany
    {
        return $this->hasMany(TaskChecklistItem::class);
    }

    public function slaCycles(): HasMany
    {
        return $this->hasMany(TaskSlaCycle::class);
    }

    public function parentTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    public function subTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_task_id');
    }

    public function sourceWorkflowHistory(): BelongsTo
    {
        return $this->belongsTo(WorkflowHistory::class, 'source_workflow_history_id');
    }
}
