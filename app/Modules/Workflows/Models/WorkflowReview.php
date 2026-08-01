<?php

namespace App\Modules\Workflows\Models;

use App\Models\User;
use App\Modules\Projects\Models\Project;
use App\Modules\Workflows\Enums\WorkflowReviewDecision;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkflowReview extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'project_id',
        'workflow_step_id',
        'submission_history_id',
        'entry_task_id',
        'review_task_id',
        'reviewer_id',
        'decision',
        'reason',
        'started_at',
        'decided_at',
    ];

    protected $casts = [
        'decision' => WorkflowReviewDecision::class,
        'started_at' => 'datetime',
        'decided_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function workflowStep()
    {
        return $this->belongsTo(WorkflowStep::class);
    }

    public function submissionHistory()
    {
        return $this->belongsTo(WorkflowHistory::class, 'submission_history_id');
    }

    public function entryTask()
    {
        return $this->belongsTo(Task::class, 'entry_task_id');
    }

    public function reviewTask()
    {
        return $this->belongsTo(Task::class, 'review_task_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
