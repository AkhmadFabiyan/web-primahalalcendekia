<?php

namespace App\Modules\Workflows\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Modules\Workflows\Enums\SlaEventType;

class TaskSlaEvent extends Model
{
    protected $guarded = [];

    protected $casts = [
        'event_type' => SlaEventType::class,
        'occurred_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function cycle()
    {
        return $this->belongsTo(TaskSlaCycle::class, 'task_sla_cycle_id');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }
}
