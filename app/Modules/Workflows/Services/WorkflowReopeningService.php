<?php

namespace App\Modules\Workflows\Services;

use App\Events\WorkflowStatusReverted;
use App\Models\User;
use App\Modules\Projects\Enums\ProjectStatus;
use App\Modules\Projects\Models\Project;
use App\Modules\Workflows\Enums\TaskStatus;
use App\Modules\Workflows\Enums\WorkflowStatus;
use App\Modules\Workflows\Models\Task;
use App\Modules\Workflows\Models\WorkflowHistory;
use App\Modules\Workflows\Models\WorkflowStep;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WorkflowReopeningService
{
    /**
     * Reopen a workflow track (e.g., ENTRY_PROGRESS or AUDITOR_PROGRESS).
     * 
     * @param string $projectId
     * @param string $workflowTrack 'ENTRY_PROGRESS' atau 'AUDITOR_PROGRESS'
     * @param string $reopenedStatus Status baru tujuan reversion
     * @param User $actor User yang melakukan aksi
     * @param string $reason Alasan pembukaan kembali
     */
    public function reopen(string $projectId, string $workflowTrack, string $reopenedStatus, User $actor, string $reason): void
    {
        DB::transaction(function () use ($projectId, $workflowTrack, $reopenedStatus, $actor, $reason) {
            $project = Project::where('id', $projectId)->lockForUpdate()->firstOrFail();

            // 1. Otorisasi
            if (!$actor->hasRole(['Super Admin', 'Manager Operasional'])) {
                throw new Exception("Anda tidak memiliki kewenangan untuk membuka kembali workflow.");
            }

            // 2. Cek Invoice Negara
            // (Mock Invoice check: di aplikasi sungguhan, cek apakah ada draft Invoice Negara)
            // Asumsi: jika status sudah sertifikat terbit atau lunas, tidak boleh reversion
            $invalidStatuses = [
                ProjectStatus::WAITING_CERTIFICATE,
                ProjectStatus::CERTIFICATE_ISSUED,
                ProjectStatus::WAITING_SETTLEMENT,
                ProjectStatus::COMPLETED,
            ];
            
            if (in_array($project->status, $invalidStatuses)) {
                throw new Exception("Reversion tidak diizinkan karena Invoice Negara sudah diterbitkan atau Project sudah melampaui batas.");
            }

            // TODO: Nanti jika ada modul Invoice Negara (Phase 23), cek khusus ke tabel Invoice Negara
            // if ($invoiceService->hasActiveGovernmentInvoice($project->id)) { throw new Exception(...) }

            $tracker = WorkflowStep::where('project_id', $project->id)
                ->where('step_code', $workflowTrack)
                ->lockForUpdate()
                ->firstOrFail();

            $oldStatus = $tracker->status->value;

            // 3. Update Tracker Status
            $tracker->status = WorkflowStatus::tryFrom($reopenedStatus);
            $tracker->last_changed_by = $actor->id;
            $tracker->save();

            // 4. Catat History
            WorkflowHistory::create([
                'project_id' => $project->id,
                'workflow_step_id' => $tracker->id,
                'from_status' => $oldStatus,
                'to_status' => $reopenedStatus,
                'actor_id' => $actor->id,
            ]);

            // 5. Activity Log
            activity()
                ->performedOn($project)
                ->causedBy($actor)
                ->event('workflow_reopened')
                ->withProperties([
                    'workflow_track' => $workflowTrack,
                    'old_status' => $oldStatus,
                    'new_status' => $reopenedStatus,
                    'reason' => $reason
                ])
                ->log("Membuka kembali {$workflowTrack} ke status {$reopenedStatus} dengan alasan: {$reason}");

            // 6. Manipulasi Task (Contoh: mencari task aktif / selesai terakhir di track tersebut untuk dibuka)
            // Di sini kita bisa mengembalikan task terakhir menjadi IN_PROGRESS atau TODO
            $lastTask = Task::where('project_id', $project->id)
                ->where('task_type', $workflowTrack === 'ENTRY_PROGRESS' ? \App\Modules\Workflows\Enums\TaskType::ENTRY_PROCESS->value : \App\Modules\Workflows\Enums\TaskType::AUDITOR_REVIEW->value)
                ->orderBy('created_at', 'desc')
                ->lockForUpdate()
                ->first();

            if ($lastTask && $lastTask->status === TaskStatus::COMPLETED) {
                $lastTask->status = TaskStatus::IN_PROGRESS;
                $lastTask->completed_at = null;
                $lastTask->save();

                activity()
                    ->performedOn($lastTask)
                    ->causedBy($actor)
                    ->event('task_reopened')
                    ->log("Task dibuka kembali terkait reopening workflow.");
            }

            // 7. Notifikasi PIC (Abaikan sementara jika modul notifikasi belum siap, atau dispatch event notifikasi)
            // event(new \App\Events\WorkflowReopenedNotification(...));

            // 8. Dispatch event reversion
            event(new WorkflowStatusReverted(
                $project->id,
                $workflowTrack,
                $oldStatus,
                $reopenedStatus,
                $actor->id,
                $reason
            ));
        });
    }
}
