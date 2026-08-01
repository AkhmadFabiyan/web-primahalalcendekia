<?php

namespace App\Modules\Projects\Services;

use App\Models\User;
use App\Modules\Projects\Enums\ArchiveVisibility;
use App\Modules\Projects\Enums\ProjectArchiveStatus;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\ProjectArchive;
use App\Modules\Projects\Models\ProjectArchiveItem;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProjectArchiveManifestService
{
    public function generate(Project $project, ?User $user = null): ProjectArchive
    {
        return DB::transaction(function () use ($project, $user) {
            // 1. Invalidate active archive if exists
            $activeArchive = $project->archives()
                ->whereNull('invalidated_at')
                ->latest()
                ->first();
                
            $nextVersion = 1;
            
            if ($activeArchive) {
                $activeArchive->update(['invalidated_at' => now()]);
                $nextVersion = $activeArchive->archive_version + 1;
            }

            // 2. Create new archive record
            $archive = $project->archives()->create([
                'archive_version' => $nextVersion,
                'status' => ProjectArchiveStatus::NOT_CREATED,
                'generated_at' => now(),
                'generated_by' => $user?->id,
            ]);

            // 3. Collect items
            $this->collectDocuments($project, $archive);
            $this->collectInvoices($project, $archive);
            $this->collectPayments($project, $archive);
            $this->collectGovernmentInvoice($project, $archive);
            $this->collectCertificate($project, $archive);
            
            return $archive;
        });
    }

    protected function createItem(ProjectArchive $archive, $model, $media, string $category, string $docName, ArchiveVisibility $visibility, ?string $version = null): void
    {
        if (!$media) return;

        $archive->items()->create([
            'source_type' => get_class($model),
            'source_id' => $model->id,
            'media_id' => $media->id,
            'category' => $category,
            'document_name' => $docName,
            'document_version' => $version,
            'visibility' => $visibility,
            'mime_type' => $media->mime_type,
            'file_size' => $media->size,
            'checksum_sha256' => $media->getCustomProperty('checksum_sha256') ?? hash_file('sha256', $media->getPath()),
        ]);
    }

    protected function collectDocuments(Project $project, ProjectArchive $archive): void
    {
        // Include soft-deleted as well for history
        $documents = $project->documents()->withTrashed()->get();

        foreach ($documents as $doc) {
            $media = $doc->getFirstMedia('document-file');
            if (!$media) continue;

            $visibility = $doc->is_client_visible ? ArchiveVisibility::CLIENT : ArchiveVisibility::INTERNAL;
            
            // Map category based on document type or just use standard folder names
            $catName = $doc->documentType ? $doc->documentType->category : '01-Administrasi';
            if (empty($catName)) $catName = '01-Administrasi';

            $this->createItem(
                $archive,
                $doc,
                $media,
                $catName,
                $doc->documentType ? $doc->documentType->name : 'Document',
                $visibility,
                "v{$doc->version}"
            );
        }
    }

    protected function collectInvoices(Project $project, ProjectArchive $archive): void
    {
        foreach ($project->invoices as $invoice) {
            $media = $invoice->getFirstMedia('invoice');
            if (!$media) continue;

            // Partner invoices are not visible to client
            $visibility = $invoice->audience === 'CLIENT' ? ArchiveVisibility::CLIENT : ArchiveVisibility::INTERNAL;

            $this->createItem(
                $archive,
                $invoice,
                $media,
                '04-Invoice',
                "Invoice-{$invoice->invoice_number}",
                $visibility
            );
        }
    }

    protected function collectPayments(Project $project, ProjectArchive $archive): void
    {
        foreach ($project->invoices as $invoice) {
            foreach ($invoice->payments as $payment) {
                $media = $payment->getFirstMedia('payment-proof');
                if (!$media) continue;

                $visibility = $invoice->audience === 'CLIENT' ? ArchiveVisibility::CLIENT : ArchiveVisibility::INTERNAL;

                $this->createItem(
                    $archive,
                    $payment,
                    $media,
                    '05-Pembayaran',
                    "Bukti-Bayar-{$payment->payment_number}",
                    $visibility
                );
            }
        }
    }

    protected function collectGovernmentInvoice(Project $project, ProjectArchive $archive): void
    {
        if ($project->governmentInvoice) {
            $media = $project->governmentInvoice->getFirstMedia('government-invoice');
            if ($media) {
                // Assuming Gov Invoice is Internal only? Actually, they upload it so it's probably internal.
                // Or maybe the client uploads it?
                $this->createItem(
                    $archive,
                    $project->governmentInvoice,
                    $media,
                    '06-Invoice-Negara',
                    "Invoice-Negara-{$project->governmentInvoice->invoice_number}",
                    ArchiveVisibility::INTERNAL // Usually client handles BPJPH directly, but let's assume internal if we have it
                );
            }
            
            // Payment proof for Gov Invoice
            $paymentMedia = $project->governmentInvoice->getFirstMedia('payment-proof');
            if ($paymentMedia) {
                $this->createItem(
                    $archive,
                    $project->governmentInvoice,
                    $paymentMedia,
                    '05-Pembayaran',
                    "Bukti-Bayar-Negara-{$project->governmentInvoice->invoice_number}",
                    ArchiveVisibility::INTERNAL
                );
            }
        }
    }

    protected function collectCertificate(Project $project, ProjectArchive $archive): void
    {
        if ($project->certificate) {
            $media = $project->certificate->getFirstMedia('certificate');
            if ($media) {
                $this->createItem(
                    $archive,
                    $project->certificate,
                    $media,
                    '07-Sertifikat',
                    "Sertifikat-{$project->certificate->certificate_number}",
                    ArchiveVisibility::CLIENT
                );
            }
        }
    }
}
