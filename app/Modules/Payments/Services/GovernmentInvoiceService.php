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
    public function create(string $projectId, User $actor, array $data): void
    {
        DB::transaction(function () use ($projectId, $actor, $data) {
            $project = Project::where('id', $projectId)->lockForUpdate()->firstOrFail();

            // 1. Cek Otorisasi (Contoh: Admin Finance)
            // if (!$actor->hasRole(['Super Admin', 'Finance'])) {
            //     throw new Exception("Anda tidak memiliki kewenangan membuat Invoice Negara.");
            // }

            // 2. Cek Status Project
            if ($project->status !== ProjectStatus::WAITING_GOVERNMENT_INVOICE) {
                throw new Exception("Pembuatan Invoice Negara ditolak: Status project saat ini adalah {$project->status->getLabel()}, bukan Menunggu Invoice Negara.");
            }

            // 3. Cek Status Workflow (Validasi ganda di backend)
            $workflowA = WorkflowStep::where('project_id', $project->id)->where('step_code', 'ENTRY_PROGRESS')->first();
            $workflowB = WorkflowStep::where('project_id', $project->id)->where('step_code', 'AUDITOR_PROGRESS')->first();

            if (!$workflowA || $workflowA->status !== WorkflowStatus::ENTRY_COMPLETED) {
                throw new Exception("Pembuatan Invoice Negara ditolak: Workflow A (Entry) belum selesai.");
            }

            if (!$workflowB || $workflowB->status !== WorkflowStatus::AUDIT_REPORT_COMPLETED) {
                throw new Exception("Pembuatan Invoice Negara ditolak: Workflow B (Auditor) belum mencapai Laporan Audit Selesai.");
            }

            // 4. Cek apakah ada invoice negara yang sudah aktif (mencegah duplikat)
            // TODO: (Diimplementasikan di Phase 23)
            // if ($this->hasActiveGovernmentInvoice($project->id)) {
            //     throw new Exception("Invoice Negara sudah dibuat untuk project ini.");
            // }

            // Logika pembuatan invoice dilanjutkan di sini pada Phase 23...
            // $invoice = Invoice::create([...]);
        });
    }
}
