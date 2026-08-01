<?php

namespace App\Modules\Projects\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use App\Modules\Projects\Enums\AssignmentRole;

class ProjectAssignment extends Model
{
    use HasUuids, HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'assignment_role' => AssignmentRole::class,
            'assigned_at' => 'datetime',
            'ended_at' => 'datetime',
            'is_primary' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
