<?php

namespace App\Modules\Projects\Models;

use App\Modules\Projects\Enums\ArchiveVisibility;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectArchiveItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'project_archive_id',
        'source_type',
        'source_id',
        'media_id',
        'category',
        'document_name',
        'document_version',
        'visibility',
        'mime_type',
        'file_size',
        'checksum_sha256',
    ];

    protected $casts = [
        'visibility' => ArchiveVisibility::class,
        'file_size' => 'integer',
    ];

    public function archive(): BelongsTo
    {
        return $this->belongsTo(ProjectArchive::class, 'project_archive_id');
    }
}
