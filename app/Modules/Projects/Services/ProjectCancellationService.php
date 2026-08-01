<?php

namespace App\Modules\Projects\Services;

use App\Modules\Projects\Models\Project;
use App\Models\User;
use App\Modules\Projects\Enums\ProjectStatus;
use App\Modules\Payments\Enums\InvoiceStatus;
use App\Modules\Workflows\Enums\TaskStatus;
use Exception;
use Illuminate\Support\Facades\DB;

class ProjectCancellationService
{
    public function cancel(Project $project, string $reason, User $actor): void
    {
        DB::transaction(function () use ($project, $reason, $actor) {
            $project = Project::where('id', $project->id)->lockForUpdate()->first();

            // 1. Validasi: Project belum COMPLETED atau CANCELLED
            if ($project->status === ProjectStatus::COMPLETED || $project->status === ProjectStatus::CANCELLED) {
                throw new Exception("Project yang sudah selesai atau dibatalkan tidak dapat dibatalkan kembali.");
            }

            // 2. Validasi: Alasan wajib ada
            if (empty(trim($reason))) {
                throw new Exception("Alasan pembatalan wajib diisi.");
            }

            // 3. Validasi: Project belum memiliki sertifikat. Jika ada sertifikat, tidak boleh dibatalkan sembarangan.
            if (\App\Modules\Projects\Models\Certificate::where('project_id', $project->id)->exists()) {
                throw new Exception("Project yang telah menerbitkan Sertifikat tidak dapat dibatalkan melalui aksi normal.");
            }

            // 4. Cek Payment verifikasi berjalan
            $hasVerifyingPayment = $project->invoices()
                ->whereHas('payments', function($q) {
                    $q->where('status', \App\Modules\Payments\Enums\PaymentStatus::PENDING);
                })->exists();

            if ($hasVerifyingPayment) {
                throw new Exception("Terdapat pembayaran yang sedang menunggu verifikasi. Tolak atau verifikasi terlebih dahulu.");
            }

            // --- CASCADE CANCELLATION ---

            // A. Batalkan Invoice yang masih dapat dibatalkan
            // DRAFT -> CANCELLED
            $project->invoices()->where('status', InvoiceStatus::DRAFT)->update(['status' => InvoiceStatus::CANCELLED]);

            // PUBLISHED -> CANCELLED jika belum memiliki verified payment
            $publishedInvoices = $project->invoices()->where('status', InvoiceStatus::PUBLISHED)->get();
            foreach ($publishedInvoices as $inv) {
                $hasVerified = $inv->payments()->where('status', \App\Modules\Payments\Enums\PaymentStatus::VERIFIED)->exists();
                if (!$hasVerified) {
                    $inv->update(['status' => InvoiceStatus::CANCELLED]);
                }
            }
            // (PARTIAL dan PAID tidak dibatalkan)

            // B. Batalkan Task yang masih terbuka
            $openTasks = [TaskStatus::TODO, TaskStatus::IN_PROGRESS, TaskStatus::WAITING_REVIEW, TaskStatus::REVISION];
            $project->tasks()->whereIn('status', $openTasks)->update(['status' => TaskStatus::CANCELLED]);

            // C. Akhiri assignment aktif
            $project->assignments()->whereNull('ended_at')->update(['ended_at' => now()]);

            // --- CANCELLATION DATA ---
            $oldStatus = $project->status instanceof ProjectStatus ? $project->status->value : $project->status;

            // Update status dan field
            $project->update([
                'status' => ProjectStatus::CANCELLED,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
                'cancelled_by' => $actor->id,
                'cancelled_from_status' => $oldStatus,
            ]);

            // Buat Arsip
            app(ProjectArchiveManifestService::class)->generate($project, $actor);

            // D. Activity Log
            activity()
                ->useLog('projects')
                ->causedBy($actor)
                ->performedOn($project)
                ->event('cancelled')
                ->tap(function ($activity) use ($project) {
                    $activity->project_id = $project->id;
                    $activity->is_client_visible = false;
                })
                ->withProperties([
                    'old' => ['status' => $oldStatus],
                    'attributes' => ['status' => ProjectStatus::CANCELLED->value],
                    'context' => [
                        'reason' => $reason,
                        'source' => 'project_cancellation_service'
                    ]
                ])
                ->log("Project dibatalkan oleh pengguna dengan alasan: {$reason}");
        });
    }
}
