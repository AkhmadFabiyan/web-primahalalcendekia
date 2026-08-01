<?php

namespace App\Modules\Projects\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity as BaseLogsActivity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class SihalalCredential extends Model
{
    use HasUuids, LogsActivity;

    protected $table = 'sihalal_credentials';

    protected $guarded = [];

    protected $hidden = [
        'email_encrypted',
        'password_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'email_encrypted' => 'encrypted',
            'password_encrypted' => 'encrypted',
            'last_used_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        // The user specifically requested that activity logs DO NOT store old/new secret values.
        // So we will just log that an update happened, but we won't log the attributes natively.
        // We can use a generic log without attributes, or we can handle it manually in the UI/service.
        // Actually, we'll configure it to only log the event but not the specific attributes.
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->dontLogIfAttributesChangedOnly(['updated_at'])
            ->setDescriptionForEvent(fn(string $eventName) => "SIHALAL_CREDENTIAL_{$eventName}");
    }
    
    // We should override the standard Spatie Activity Log payload to ensure no secrets leak.
    public function tapActivity(\Spatie\Activitylog\Models\Activity $activity, string $eventName)
    {
        $activity->properties = collect([
            'project_id' => $this->project_id,
            'contains_secret' => false,
        ]);
        
        if ($eventName === 'updated') {
            $activity->properties = $activity->properties->merge([
                'changed_fields' => array_keys($this->getChanges()),
            ]);
        }
    }
}
