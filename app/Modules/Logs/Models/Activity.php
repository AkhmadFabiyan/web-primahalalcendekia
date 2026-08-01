<?php

namespace App\Modules\Logs\Models;

use App\Modules\Projects\Models\Project;
use Exception;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

class Activity extends SpatieActivity
{
    protected $casts = [
        'properties' => 'collection',
        'is_client_visible' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    // Append-only protection
    public function update(array $attributes = [], array $options = [])
    {
        throw new Exception('Activity Log is append-only and cannot be updated.');
    }

    public function delete()
    {
        throw new Exception('Activity Log is append-only and cannot be deleted.');
    }

    public function forceDelete()
    {
        throw new Exception('Activity Log is append-only and cannot be force deleted.');
    }
}
