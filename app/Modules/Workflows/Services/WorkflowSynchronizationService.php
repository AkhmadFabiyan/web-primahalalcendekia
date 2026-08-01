<?php

namespace App\Modules\Workflows\Services;

use App\Modules\Projects\Enums\ProjectStatus;
use App\Modules\Projects\Models\Project;
use App\Modules\Workflows\Enums\WorkflowStatus;
use App\Modules\Workflows\Models\WorkflowStep;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WorkflowSynchronizationService
{
    /**
     * Mengecek dan menyinkronkan status Workflow A dan B.
     * Dipanggil saat event WorkflowACompleted atau WorkflowBCompleted di-dispatch.
     */
    public function synchronizeCompletion(string $projectId): void
    {
        DB::transaction(function () use ($projectId) {
            $project = Project::where('id', $projectId)->lockForUpdate()->first();
            
            if (!$project || $project->status !== ProjectStatus::OPERATIONAL) {
                return;
            }

            $workflowA = WorkflowStep::where('project_id', $projectId)
                ->where('step_code', 'ENTRY_PROGRESS')
                ->lockForUpdate()
                ->first();

            $workflowB = WorkflowStep::where('project_id', $projectId)
                ->where('step_code', 'AUDITOR_PROGRESS')
                ->lockForUpdate()
                ->first();

            if (!$workflowA || !$workflowB) {
                return;
            }

            // Cek apakah Workflow A selesai (ENTRY_COMPLETED)
            $isACompleted = $workflowA->status === WorkflowStatus::ENTRY_COMPLETED;
            
            // Cek apakah Workflow B selesai (AUDIT_REPORT_COMPLETED)
            $isBCompleted = $workflowB->status === WorkflowStatus::AUDIT_REPORT_COMPLETED;

            if ($isACompleted && $isBCompleted) {
                $oldStatus = $project->status->value;
                $project->status = ProjectStatus::WAITING_GOVERNMENT_INVOICE;
                $project->save();

                activity()
                    ->performedOn($project)
                    ->event('project_status_updated')
                    ->withProperties([
                        'old_status' => $oldStatus,
                        'new_status' => ProjectStatus::WAITING_GOVERNMENT_INVOICE->value,
                        'reason' => 'Workflow A & B Completed'
                    ])
                    ->log("Status Project berubah menjadi WAITING_GOVERNMENT_INVOICE secara otomatis karena Workflow A & B selesai.");
                
                Log::info("Project {$project->id} transitioned to WAITING_GOVERNMENT_INVOICE because Workflow A and B are completed.");
            }
        });
    }

    /**
     * Dipanggil saat ada event reversion untuk menurunkan kembali status project
     * jika syarat sinkronisasi tidak lagi terpenuhi.
     */
    public function revertToOperational(string $projectId): void
    {
        DB::transaction(function () use ($projectId) {
            $project = Project::where('id', $projectId)->lockForUpdate()->first();
            
            // Hanya bisa mundur jika project masih ada di status WAITING_GOVERNMENT_INVOICE
            if (!$project || $project->status !== ProjectStatus::WAITING_GOVERNMENT_INVOICE) {
                return;
            }

            $workflowA = WorkflowStep::where('project_id', $projectId)
                ->where('step_code', 'ENTRY_PROGRESS')
                ->lockForUpdate()
                ->first();

            $workflowB = WorkflowStep::where('project_id', $projectId)
                ->where('step_code', 'AUDITOR_PROGRESS')
                ->lockForUpdate()
                ->first();

            if (!$workflowA || !$workflowB) {
                return;
            }

            $isACompleted = $workflowA->status === WorkflowStatus::ENTRY_COMPLETED;
            $isBCompleted = $workflowB->status === WorkflowStatus::AUDIT_REPORT_COMPLETED;

            // Jika SALAH SATU tidak lagi selesai, maka project mundur ke OPERATIONAL
            if (!$isACompleted || !$isBCompleted) {
                $oldStatus = $project->status->value;
                $project->status = ProjectStatus::OPERATIONAL;
                $project->save();

                activity()
                    ->performedOn($project)
                    ->event('project_status_updated')
                    ->withProperties([
                        'old_status' => $oldStatus,
                        'new_status' => ProjectStatus::OPERATIONAL->value,
                        'reason' => 'Sinkronisasi Workflow Batal (Reversion)'
                    ])
                    ->log("Status Project dikembalikan ke OPERATIONAL karena ada workflow yang dibuka kembali.");
                
                Log::info("Project {$project->id} reverted to OPERATIONAL because a workflow was reopened.");
            }
        });
    }
}
