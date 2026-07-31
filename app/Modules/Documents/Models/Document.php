<?php

namespace App\Modules\Documents\Models;

use App\Models\User;
use App\Modules\Documents\Enums\DocumentRecordStatus;
use App\Modules\Projects\Models\Project;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Document extends Model implements HasMedia
{
    use HasUuids, SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'project_id',
        'document_type_id',
        'version',
        'is_client_visible',
        'uploaded_by',
        'uploaded_at',
        'status',
    ];

    protected $casts = [
        'version' => 'integer',
        'is_client_visible' => 'boolean',
        'uploaded_at' => 'datetime',
        'status' => DocumentRecordStatus::class,
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('document-file')
            ->singleFile(); // constraint: only one file per document record
    }
}
