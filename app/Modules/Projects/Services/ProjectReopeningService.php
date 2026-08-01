<?php

namespace App\Modules\Projects\Services;

use App\Modules\Projects\Models\Project;
use App\Models\User;
use App\Modules\Projects\Enums\ProjectStatus;
use Exception;
use Illuminate\Support\Facades\DB;

class ProjectReopeningService
{
    public function reopen(Project $project, string $reason, User $actor): void
    {
        DB::transaction(function () use ($project, $reason, $actor) {
            $project = Project::where('id', $project->id)->lockForUpdate()->first();

            // 1. Validasi: Project harus COMPLETED atau CANCELLED
            if ($project->status !== ProjectStatus::COMPLETED && $project->status !== ProjectStatus::CANCELLED) {
                throw new Exception("Project belum terkunci, tidak perlu dibuka kembali.");
            }

            // 2. Validasi: Alasan wajib ada
            if (empty(trim($reason))) {
                throw new Exception("Alasan pembukaan kembali wajib diisi.");
            }

            $oldStatusLabel = $project->status === ProjectStatus::COMPLETED ? 'Selesai' : 'Dibatalkan';

            // 3. Tentukan status tujuan
            $targetStatus = ProjectStatus::WAITING_ACTIVATION;
            if ($project->status === ProjectStatus::CANCELLED && $project->cancelled_from_status) {
                $targetStatusEnum = ProjectStatus::tryFrom($project->cancelled_from_status);
                if ($targetStatusEnum) {
                    $targetStatus = $targetStatusEnum;
                }
            } else {
                // Jika dari COMPLETED, buka kembali menjadi CERTIFICATE_ISSUED atau WAITING_SETTLEMENT 
                // Tergantung apakah ada sertifikat
                if ($project->certificate()->exists()) {
                    $targetStatus = ProjectStatus::CERTIFICATE_ISSUED;
                } else {
                    $targetStatus = ProjectStatus::WAITING_SETTLEMENT;
                }
            }

            // 4. Update Project (bypass model lock trait)
            $project->update([
                'status' => $targetStatus,
                'completed_at' => null,
                'cancelled_at' => null,
                'cancelled_by' => null,
                'cancellation_reason' => null,
                'cancelled_from_status' => null,
            ]);

            // 5. Invalidate Archive
            $activeArchive = $project->archives()->whereNull('invalidated_at')->latest()->first();
            if ($activeArchive) {
                $activeArchive->update(['invalidated_at' => now()]);
            }

            // 6. Activity Log
            activity()
                ->useLog('projects')
                ->causedBy($actor)
                ->performedOn($project)
                ->event('reopened')
                ->tap(function ($activity) use ($project) {
                    $activity->project_id = $project->id;
                    $activity->is_client_visible = false;
                })
                ->withProperties([
                    'old' => ['status' => $project->getOriginal('status')?->value],
                    'attributes' => ['status' => $targetStatus->value],
                    'context' => [
                        'reason' => $reason,
                        'source' => 'project_reopening_service'
                    ]
                ])
                ->log("Project dibuka kembali dari status {$oldStatusLabel} oleh pengguna dengan alasan: {$reason}");
        });
    }
}
