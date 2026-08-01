<?php

namespace App\Modules\Projects\Services;

use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Enums\ProjectStatus;
use App\Modules\Payments\Enums\InvoiceStatus;
use App\Modules\Payments\Enums\PaymentStatus;
use Illuminate\Support\Facades\DB;
use Exception;

class ProjectCompletionService
{
    public function checkCompletion(Project $project): void
    {
        DB::transaction(function () use ($project) {
            $project = Project::where('id', $project->id)->lockForUpdate()->first();

            // Hanya proses jika statusnya CERTIFICATE_ISSUED atau WAITING_SETTLEMENT
            if (!in_array($project->status, [ProjectStatus::CERTIFICATE_ISSUED, ProjectStatus::WAITING_SETTLEMENT])) {
                return;
            }

            // Gunakan ProjectClosureReadinessService
            $readiness = ProjectClosureReadinessService::evaluate($project);
            $isReady = ProjectClosureReadinessService::isReady($readiness);

            if (!$isReady) {
                // Pastikan status menjadi WAITING_SETTLEMENT jika sudah CERTIFICATE_ISSUED dan belum siap
                if ($project->status === ProjectStatus::CERTIFICATE_ISSUED) {
                    $project->update(['status' => ProjectStatus::WAITING_SETTLEMENT]);
                }
                return;
            }

            $oldStatus = $project->getOriginal('status');
            // Lolos semua, tandai Project COMPLETED
            $project->update([
                'status' => ProjectStatus::COMPLETED,
                'completed_at' => now(),
            ]);

            // Buat Arsip
            app(ProjectArchiveManifestService::class)->generate($project);

            activity()
                ->useLog('projects')
                ->performedOn($project)
                ->event('completed')
                ->tap(function ($activity) use ($project) {
                    $activity->project_id = $project->id;
                    $activity->is_client_visible = true;
                })
                ->withProperties([
                    'old' => ['status' => $oldStatus instanceof ProjectStatus ? $oldStatus->value : $oldStatus],
                    'attributes' => ['status' => ProjectStatus::COMPLETED->value],
                    'context' => [
                        'source' => 'project_completion_service'
                    ]
                ])
                ->log("Seluruh kewajiban operasional dan finansial Project telah selesai. Status menjadi Selesai.");
        });
    }
}
