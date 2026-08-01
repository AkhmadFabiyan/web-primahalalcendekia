<?php

namespace App\Modules\Projects\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Certificate extends Model implements HasMedia
{
    use HasUuids, InteractsWithMedia, \App\Modules\Projects\Traits\LocksWhenProjectLocked;

    protected $fillable = [
        'project_id',
        'certificate_number',
        'issued_at',
        'valid_until',
        'uploaded_by',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'valid_until' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('certificate')
            ->singleFile()
            ->acceptsMimeTypes(['application/pdf'])
            ->useDisk('private');
    }
}
