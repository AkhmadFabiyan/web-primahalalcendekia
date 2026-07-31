<?php

namespace App\Modules\Documents\Services;

use App\Models\User;
use App\Modules\Documents\Enums\DocumentModuleStatus;
use App\Modules\Documents\Enums\DocumentRecordStatus;
use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Models\DocumentType;
use App\Modules\Documents\Models\ProjectDocumentRequirement;
use App\Modules\Projects\Models\Project;
use App\Modules\Workflows\Services\TaskService;
use App\Modules\Workflows\Services\WorkflowService;
use Illuminate\Support\Facades\DB;

class DocumentService
{
    public function __construct(
        protected WorkflowService $workflowService,
        protected TaskService $taskService
    ) {
    }

    /**
     * Snapshot aktif Document Types ke Project Requirements.
     * Dipanggil saat Project Activation.
     */
    public function snapshotRequirements(Project $project): void
    {
        DB::transaction(function () use ($project) {
            $types = DocumentType::where('is_active', true)->get();

            foreach ($types as $type) {
                ProjectDocumentRequirement::updateOrCreate(
                    [
                        'project_id' => $project->id,
                        'document_type_id' => $type->id,
                    ],
                    [
                        'is_required' => $type->is_required,
                        'sort_order' => $type->sort_order,
                    ]
                );
            }

            // Inisialisasi status workflow
            $this->workflowService->updateStepStatus(
                $project->id,
                'DOCUMENT_ADMINISTRATION',
                'A',
                DocumentModuleStatus::NOT_STARTED->value,
                null,
                true // is_required
            );
        });
    }

    /**
     * Upload dokumen baru (v1).
     */
    public function uploadDocument(Project $project, int $documentTypeId, $file, User $uploader, bool $isClientVisible = false): Document
    {
        return DB::transaction(function () use ($project, $documentTypeId, $file, $uploader, $isClientVisible) {
            // Lock project
            $project->lockForUpdate()->find($project->id);

            // Verifikasi belum ada dokumen aktif
            $existing = Document::where('project_id', $project->id)
                ->where('document_type_id', $documentTypeId)
                ->whereIn('status', [DocumentRecordStatus::UPLOADED->value])
                ->first();

            if ($existing) {
                throw new \Exception('Dokumen sudah ada. Gunakan Replace untuk mengganti versi.');
            }

            $document = Document::create([
                'project_id' => $project->id,
                'document_type_id' => $documentTypeId,
                'version' => 1,
                'is_client_visible' => $isClientVisible,
                'uploaded_by' => $uploader->id,
                'uploaded_at' => now(),
                'status' => DocumentRecordStatus::UPLOADED,
            ]);

            $document->addMedia($file)->toMediaCollection('document-file', 'private');

            activity()
                ->performedOn($document)
                ->causedBy($uploader)
                ->event('upload')
                ->log('Dokumen diunggah.');

            $this->checkCompleteness($project);

            return $document;
        });
    }

    /**
     * Ganti dokumen dengan versi baru (v+1).
     */
    public function replaceDocument(Project $project, int $documentTypeId, $file, User $uploader, bool $isClientVisible = false): Document
    {
        return DB::transaction(function () use ($project, $documentTypeId, $file, $uploader, $isClientVisible) {
            $project->lockForUpdate()->find($project->id);

            $oldDocument = Document::where('project_id', $project->id)
                ->where('document_type_id', $documentTypeId)
                ->where('status', DocumentRecordStatus::UPLOADED->value)
                ->lockForUpdate()
                ->first();

            $nextVersion = 1;
            if ($oldDocument) {
                $nextVersion = $oldDocument->version + 1;
            } else {
                // Cari max versi
                $maxVersion = Document::where('project_id', $project->id)
                    ->where('document_type_id', $documentTypeId)
                    ->max('version');
                $nextVersion = $maxVersion ? $maxVersion + 1 : 1;
            }

            $newDocument = Document::create([
                'project_id' => $project->id,
                'document_type_id' => $documentTypeId,
                'version' => $nextVersion,
                'is_client_visible' => $isClientVisible,
                'uploaded_by' => $uploader->id,
                'uploaded_at' => now(),
                'status' => DocumentRecordStatus::UPLOADED,
            ]);

            // Save file first
            $newDocument->addMedia($file)->toMediaCollection('document-file', 'private');

            // If file saved successfully, replace old document status
            if ($oldDocument) {
                $oldDocument->update(['status' => DocumentRecordStatus::REPLACED]);
            }

            activity()
                ->performedOn($newDocument)
                ->causedBy($uploader)
                ->event('replace')
                ->log('Dokumen diganti dengan versi baru.');

            $this->checkCompleteness($project);

            return $newDocument;
        });
    }

    public function archiveDocument(Document $document, User $actor): void
    {
        DB::transaction(function () use ($document, $actor) {
            $document->project->lockForUpdate()->find($document->project_id);
            
            $document->update(['status' => DocumentRecordStatus::ARCHIVED]);
            $document->delete(); // Soft delete

            activity()
                ->performedOn($document)
                ->causedBy($actor)
                ->event('archive')
                ->log('Dokumen diarsipkan.');

            $this->checkCompleteness($document->project);
        });
    }

    public function requestRevision(ProjectDocumentRequirement $requirement, string $reason, User $actor): void
    {
        DB::transaction(function () use ($requirement, $reason, $actor) {
            $project = $requirement->project;
            $project->lockForUpdate()->find($project->id);

            $requirement->update([
                'revision_requested_at' => now(),
                'revision_requested_by' => $actor->id,
                'revision_reason' => $reason,
                'revision_resolved_at' => null,
                'revision_resolved_by' => null,
            ]);

            $this->workflowService->updateStepStatus(
                $project->id,
                'DOCUMENT_ADMINISTRATION',
                'A',
                DocumentModuleStatus::REVISION->value,
                "Permintaan revisi: {$requirement->documentType->name}"
            );

            activity()
                ->performedOn($requirement)
                ->causedBy($actor)
                ->event('revision_requested')
                ->log("Revisi diminta untuk dokumen {$requirement->documentType->name}");
        });
    }

    public function resolveRevision(ProjectDocumentRequirement $requirement, User $actor): void
    {
        DB::transaction(function () use ($requirement, $actor) {
            $project = $requirement->project;
            $project->lockForUpdate()->find($project->id);

            $requirement->update([
                'revision_resolved_at' => now(),
                'revision_resolved_by' => $actor->id,
            ]);

            activity()
                ->performedOn($requirement)
                ->causedBy($actor)
                ->event('revision_resolved')
                ->log("Revisi selesai untuk dokumen {$requirement->documentType->name}");

            $this->checkCompleteness($project);
        });
    }

    /**
     * Evaluasi kelengkapan dokumen dan perbarui Workflow.
     * Idempoten: hanya memancarkan event / menyelesaikan task jika ada transisi menuju COMPLETE.
     */
    public function checkCompleteness(Project $project): void
    {
        $requirements = ProjectDocumentRequirement::where('project_id', $project->id)->get();
        $activeDocuments = Document::where('project_id', $project->id)
            ->where('status', DocumentRecordStatus::UPLOADED->value)
            ->get();

        $isComplete = true;
        $hasAnyDocument = $activeDocuments->isNotEmpty();
        $hasOpenRevision = false;

        foreach ($requirements as $req) {
            if ($req->revision_requested_at && !$req->revision_resolved_at) {
                $hasOpenRevision = true;
            }

            if ($req->is_required) {
                $hasDoc = $activeDocuments->contains('document_type_id', $req->document_type_id);
                // Also need to ensure the document has media
                $doc = $activeDocuments->where('document_type_id', $req->document_type_id)->first();
                if (!$hasDoc || !$doc->hasMedia('document-file')) {
                    $isComplete = false;
                }
            }
        }

        $newStatus = DocumentModuleStatus::NOT_STARTED;

        if ($hasOpenRevision) {
            $newStatus = DocumentModuleStatus::REVISION;
        } elseif ($isComplete && $hasAnyDocument) {
            $newStatus = DocumentModuleStatus::COMPLETE;
        } elseif ($hasAnyDocument) {
            $newStatus = DocumentModuleStatus::IN_PROGRESS;
        }

        $currentStep = $this->workflowService->getStep($project->id, 'DOCUMENT_ADMINISTRATION');
        $currentStatus = $currentStep ? $currentStep->status : null;

        if ($currentStatus !== $newStatus->value) {
            $this->workflowService->updateStepStatus(
                $project->id,
                'DOCUMENT_ADMINISTRATION',
                'A',
                $newStatus->value
            );

            if ($newStatus === DocumentModuleStatus::COMPLETE) {
                // Selesaikan task Admin
                $this->taskService->completeInitialTask($project, null, 'Dokumen persyaratan lengkap.');
                // Event ini tidak otomatis trigger Entry (Sesuai batas Phase 15).
                event(new \App\Modules\Documents\Events\DocumentsCompleted($project));
            }
        }
    }
}
