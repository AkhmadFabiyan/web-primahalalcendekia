<?php

namespace App\Modules\Payments\Services;

use App\Models\User;
use App\Modules\Projects\Enums\ProjectStatus;
use App\Modules\Projects\Models\Project;
use App\Modules\Workflows\Enums\WorkflowStatus;
use App\Modules\Workflows\Models\WorkflowStep;
use Exception;
use Illuminate\Support\Facades\DB;

class GovernmentInvoiceService
{
    /**
     * Membuat Invoice Negara.
     * Ini juga berfungsi sebagai guard untuk memastikan Invoice Negara tidak bisa dibuat
     * kecuali semua syarat Workflow A dan Workflow B (Phase 22) telah terpenuhi.
     */
    public function create(string $projectId, User $actor, array $data): \App\Modules\Payments\Models\Invoice
    {
        return DB::transaction(function () use ($projectId, $actor, $data) {
            $project = Project::where('id', $projectId)->lockForUpdate()->firstOrFail();

            if (!$actor->hasRole(['Super Admin', 'Admin Perusahaan'])) {
                throw new Exception("Anda tidak memiliki kewenangan membuat Invoice Negara.");
            }

            if ($project->status !== ProjectStatus::WAITING_GOVERNMENT_INVOICE) {
                throw new Exception("Pembuatan Invoice Negara ditolak: Status project saat ini adalah {$project->status->getLabel()}, bukan Menunggu Invoice Negara.");
            }

            $workflowA = WorkflowStep::where('project_id', $project->id)->where('step_code', 'ENTRY_PROGRESS')->first();
            $workflowB = WorkflowStep::where('project_id', $project->id)->where('step_code', 'AUDITOR_PROGRESS')->first();

            if (!$workflowA || $workflowA->status !== WorkflowStatus::ENTRY_COMPLETED) {
                throw new Exception("Pembuatan Invoice Negara ditolak: Workflow A (Entry) belum selesai.");
            }

            if (!$workflowB || $workflowB->status !== WorkflowStatus::AUDIT_REPORT_COMPLETED) {
                throw new Exception("Pembuatan Invoice Negara ditolak: Workflow B (Auditor) belum mencapai Laporan Audit Selesai.");
            }

            $hasActiveInvoice = \App\Modules\Payments\Models\Invoice::where('project_id', $project->id)
                ->where('invoice_type', \App\Modules\Payments\Enums\InvoiceType::GOVERNMENT->value)
                ->where('status', '!=', \App\Modules\Payments\Enums\InvoiceStatus::CANCELLED->value)
                ->exists();

            if ($hasActiveInvoice) {
                throw new Exception("Invoice Negara sudah dibuat untuk project ini.");
            }

            $nominal = $data['nominal'] ?? 0;
            if ($nominal <= 0) {
                throw new Exception("Nominal Invoice Negara harus lebih besar dari nol.");
            }

            if (empty($data['invoice_number'])) {
                throw new Exception("Nomor Invoice Negara wajib diisi.");
            }
            
            if (empty($data['due_date'])) {
                throw new Exception("Tanggal Jatuh Tempo wajib diisi.");
            }

            if (empty($data['file'])) {
                throw new Exception("File Invoice Negara wajib diunggah.");
            }

            $invoice = \App\Modules\Payments\Models\Invoice::create([
                'project_id' => $project->id,
                'invoice_number' => $data['invoice_number'],
                'invoice_type' => \App\Modules\Payments\Enums\InvoiceType::GOVERNMENT->value,
                'billing_group_id' => \Illuminate\Support\Str::uuid(),
                'audience' => \App\Modules\Payments\Enums\InvoiceAudience::CLIENT->value,
                'partner_id' => null, // Invoice negara tidak dicatat untuk partner
                'subtotal' => $nominal,
                'discount_total' => 0,
                'status' => \App\Modules\Payments\Enums\InvoiceStatus::PUBLISHED->value, // Invoice eksternal langsung diterbitkan
                'due_date' => $data['due_date'],
                'issued_at' => now(),
                'published_at' => now(),
            ]);

            // Tambahkan file PDF ke Media Library
            $invoice->addMedia($data['file'])
                ->toMediaCollection('government-invoice-document');

            activity()
                ->performedOn($project)
                ->causedBy($actor)
                ->event('government_invoice_created')
                ->withProperties([
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'nominal' => $invoice->subtotal,
                ])
                ->log("Invoice Negara berhasil diunggah.");

            return $invoice;
        });
    }
}
