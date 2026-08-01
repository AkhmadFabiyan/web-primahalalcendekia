<?php

namespace App\Modules\Projects\Services;

use App\Models\User;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\Certificate;
use App\Modules\Projects\Enums\ProjectStatus;
use App\Modules\Workflows\Models\WorkflowStep;
use App\Modules\Workflows\Enums\WorkflowStatus;
use App\Modules\Payments\Models\Invoice;
use App\Modules\Payments\Enums\InvoiceType;
use App\Modules\Payments\Enums\InvoiceStatus;
use Illuminate\Support\Facades\DB;
use Exception;

class CertificateService
{
    public function issueCertificate(Project $project, array $data, $file, User $actor): Certificate
    {
        return DB::transaction(function () use ($project, $data, $file, $actor) {
            $project = Project::where('id', $project->id)->lockForUpdate()->first();

            // 1. Validasi Project
            if ($project->status !== ProjectStatus::WAITING_CERTIFICATE) {
                throw new Exception("Sertifikat tidak dapat diterbitkan karena status Project bukan Menunggu Sertifikat.");
            }

            // 2. Validasi Invoice Negara (harus PAID)
            $invoice = Invoice::where('project_id', $project->id)
                ->where('invoice_type', InvoiceType::GOVERNMENT->value)
                ->first();

            if (!$invoice || $invoice->status !== InvoiceStatus::PAID) {
                throw new Exception("Invoice Negara belum dilunasi.");
            }

            // 3. Validasi Workflow A & B Selesai
            $workflowA = WorkflowStep::where('project_id', $project->id)
                ->where('step_code', 'ENTRY_PROGRESS')
                ->first();

            if (!$workflowA || $workflowA->status !== WorkflowStatus::ENTRY_COMPLETED) {
                throw new Exception("Workflow A (Entry) belum selesai.");
            }

            $workflowB = WorkflowStep::where('project_id', $project->id)
                ->where('step_code', 'AUDITOR_PROGRESS')
                ->first();

            if (!$workflowB || $workflowB->status !== WorkflowStatus::AUDIT_REPORT_COMPLETED) {
                throw new Exception("Workflow B (Auditor) belum selesai.");
            }

            // 4. Validasi belum ada Sertifikat
            if ($project->certificate()->exists()) {
                throw new Exception("Sertifikat sudah diterbitkan untuk Project ini.");
            }

            // 5. Validasi Valid Until (harus sesudah Issued At)
            if (!empty($data['valid_until']) && strtotime($data['valid_until']) <= strtotime($data['issued_at'])) {
                throw new Exception("Masa Berlaku Sertifikat harus setelah Tanggal Terbit.");
            }

            // 6. Buat Sertifikat
            $certificate = Certificate::create([
                'project_id' => $project->id,
                'certificate_number' => $data['certificate_number'],
                'issued_at' => $data['issued_at'],
                'valid_until' => $data['valid_until'] ?? null,
                'uploaded_by' => $actor->id,
            ]);

            // 7. Simpan file
            if (empty($file)) {
                throw new Exception("File Sertifikat wajib diunggah.");
            }
            
            $certificate->addMedia($file)
                ->toMediaCollection('certificate');

            // 8. Update status Project menjadi CERTIFICATE_ISSUED
            $project->update([
                'status' => ProjectStatus::CERTIFICATE_ISSUED,
                // completed_at dihapus karena Phase 25 mengatur bahwa completed_at hanya diisi saat lunas
            ]);

            // 9. Update progress project (opsional bila diperlukan, biasanya dihitung dari task/workflow, tapi sesuai permintaan jadi 100% untuk auditor?)
            // Tidak ada kolom progress di project, progress dihitung lewat workflow steps.

            // 10. Activity log
            activity()
                ->performedOn($project)
                ->causedBy($actor)
                ->event('certificate_issued')
                ->log("Sertifikat Halal dengan nomor {$certificate->certificate_number} telah diterbitkan.");

            // 11. Selesaikan Task Sertifikat (jika ada)
            // Asumsi: task untuk sertifikat ada dalam project_tasks (kalau ada modul tasks).
            // Jika modul tasks ada, panggil update task.
            if (class_exists(\App\Modules\Tasks\Models\ProjectTask::class)) {
                $tasks = \App\Modules\Tasks\Models\ProjectTask::where('project_id', $project->id)
                    ->whereIn('task_type', ['UPLOAD_CERTIFICATE', 'ISSUE_CERTIFICATE']) // contoh
                    ->where('status', '!=', 'COMPLETED')
                    ->get();
                    
                foreach ($tasks as $task) {
                    $task->update(['status' => 'COMPLETED', 'completed_at' => now()]);
                }
            }

            // Kirim event/notification (akan ditangkap oleh listener/observer jika disetup, 
            // sementara saya panggil event custom atau default, 
            // Namun notifikasi khusus dikirim di sini bila ada event)
            if (class_exists(\App\Modules\Projects\Events\CertificateIssued::class)) {
                event(new \App\Modules\Projects\Events\CertificateIssued($project->id, $certificate->id, $actor->id));
            }
            
            // Periksa apakah Project bisa langsung COMPLETED atau butuh pelunasan komersial
            app(\App\Modules\Projects\Services\ProjectCompletionService::class)->checkCompletion($project);

            return $certificate;
        });
    }
}
