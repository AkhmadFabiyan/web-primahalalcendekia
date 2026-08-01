<?php

namespace App\Modules\Workflows\Models;

use Illuminate\Database\Eloquent\Model;
use App\Modules\Workflows\Enums\SlaCycleStatus;

class TaskSlaCycle extends Model
{
    protected $guarded = [];

    protected $casts = [
        'duration_snapshot' => 'array',
        'started_at' => 'datetime',
        'due_at' => 'datetime',
        'paused_at' => 'datetime',
        'completed_at' => 'datetime',
        'breached_at' => 'datetime',
        'last_escalated_at' => 'datetime',
        'status' => SlaCycleStatus::class,
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function policy()
    {
        return $this->belongsTo(SlaPolicy::class, 'sla_policy_id');
    }

    public function events()
    {
        return $this->hasMany(TaskSlaEvent::class);
    }
}
