<?php

namespace App\Modules\Projects\Models;

use App\Models\User;
use App\Modules\Projects\Enums\ProjectArchiveStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ProjectArchive extends Model implements HasMedia
{
    use HasUuids, InteractsWithMedia;

    protected $fillable = [
        'project_id',
        'archive_version',
        'status',
        'generated_at',
        'generated_by',
        'invalidated_at',
    ];

    protected $casts = [
        'archive_version' => 'integer',
        'status' => ProjectArchiveStatus::class,
        'generated_at' => 'datetime',
        'invalidated_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProjectArchiveItem::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('archive-internal')->useDisk('local')->singleFile();
        $this->addMediaCollection('archive-client')->useDisk('local')->singleFile();
    }
}
