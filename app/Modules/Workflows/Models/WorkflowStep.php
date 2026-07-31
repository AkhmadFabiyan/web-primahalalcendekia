<?php

namespace App\Modules\Workflows\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Modules\Projects\Models\Project;
use App\Models\User;
use App\Modules\Workflows\Enums\WorkflowLane;
use App\Modules\Workflows\Enums\WorkflowTrack;
use App\Modules\Workflows\Enums\WorkflowStatus;

class WorkflowStep extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'workflow_lane' => WorkflowLane::class,
            'track_code' => WorkflowTrack::class,
            'status' => WorkflowStatus::class,
            'is_required' => 'boolean',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function lastChangedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_changed_by');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(WorkflowHistory::class);
    }
}
