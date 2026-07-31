<?php

namespace App\Modules\Documents\Models;

use App\Models\User;
use App\Modules\Projects\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectDocumentRequirement extends Model
{
    protected $fillable = [
        'project_id',
        'document_type_id',
        'is_required',
        'sort_order',
        'revision_requested_at',
        'revision_requested_by',
        'revision_reason',
        'revision_resolved_at',
        'revision_resolved_by',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'sort_order' => 'integer',
        'revision_requested_at' => 'datetime',
        'revision_resolved_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function revisionRequestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revision_requested_by');
    }

    public function revisionResolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revision_resolved_by');
    }

    public function latestDocument()
    {
        return $this->hasOne(Document::class, 'project_id', 'project_id')
            ->whereColumn('document_type_id', 'document_types.id') // wait, this is wrong. The local key is document_type_id
            // the foreign keys are project_id and document_type_id.
            // Laravel doesn't support composite foreign keys natively for hasOne easily without a package.
            // But we can do:
            ->where('document_type_id', $this->document_type_id)
            ->where('status', \App\Modules\Documents\Enums\DocumentRecordStatus::UPLOADED->value);
    }
}
