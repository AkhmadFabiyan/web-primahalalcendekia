<?php

namespace App\Modules\Notifications\Models;

use App\Modules\Notifications\Enums\NotificationEvent;
use App\Modules\Notifications\Enums\NotificationPriority;
use App\Modules\Projects\Models\Project;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\DatabaseNotification as BaseDatabaseNotification;

class DatabaseNotification extends BaseDatabaseNotification
{
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'archived_at' => 'datetime',
        'priority' => NotificationPriority::class,
        'event_code' => NotificationEvent::class,
    ];

    /**
     * Get the project associated with the notification.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function ($notification) {
            $data = $notification->data ?? [];
            
            if (isset($data['project_id'])) {
                $notification->project_id = $data['project_id'];
                unset($data['project_id']);
            }
            if (isset($data['priority'])) {
                $notification->priority = $data['priority'];
                unset($data['priority']);
            }
            if (isset($data['event_code'])) {
                $notification->event_code = $data['event_code'];
                unset($data['event_code']);
            }
            if (isset($data['deduplication_key'])) {
                $notification->deduplication_key = $data['deduplication_key'];
                unset($data['deduplication_key']);
            }

            $notification->data = $data;
        });
    }
}
