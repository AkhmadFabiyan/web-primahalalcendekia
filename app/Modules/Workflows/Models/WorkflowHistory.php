<?php

namespace App\Modules\Workflows\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\Projects\Models\Project;
use App\Models\User;
use App\Modules\Workflows\Enums\WorkflowStatus;

class WorkflowHistory extends Model
{
    use HasUuids;

    public $timestamps = false; // We only use created_at

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'from_status' => WorkflowStatus::class,
            'to_status' => WorkflowStatus::class,
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function workflowStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
