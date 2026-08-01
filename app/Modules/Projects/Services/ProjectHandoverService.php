<?php

namespace App\Modules\Projects\Services;

use App\Models\User;
use App\Modules\Projects\Enums\AssignmentRole;
use App\Modules\Projects\Enums\ProjectStatus;
use App\Modules\Projects\Models\Project;
use App\Modules\Workflows\Enums\TaskPriority;
use App\Modules\Workflows\Enums\TaskStatus;
use App\Modules\Workflows\Enums\TaskType;
use App\Modules\Workflows\Enums\WorkflowLane;
use App\Modules\Workflows\Enums\WorkflowStatus;
use App\Modules\Workflows\Models\Task;
use App\Modules\Workflows\Models\TaskAssignmentHistory;
use App\Modules\Workflows\Models\WorkflowStep;
use App\Modules\Workflows\Services\TaskService;
use App\Modules\Documents\Services\DocumentService;
use Exception;
use Illuminate\Support\Facades\DB;

class ProjectHandoverService
{
    public function __construct(
        private TaskService $taskService,
        private DocumentService $documentService
    ) {}

    public function handoverToEntry(Project $project, User $actor): void
    {
        DB::transaction(function () use ($project, $actor) {
            // Lock project for update
            $project = Project::where('id', $project->id)->lockForUpdate()->firstOrFail();

            if ($project->status->value !== ProjectStatus::ACTIVE->value) {
                throw new Exception("Handoff ditolak: Project tidak aktif.");
            }

            // Verify Documents Complete
            $docStep = WorkflowStep::where('project_id', $project->id)
                ->where('step_code', 'DOCUMENT_ADMINISTRATION')
                ->lockForUpdate()
                ->first();

            // Re-check completeness just in case
            $this->documentService->checkCompleteness($project);
            $docStep->refresh();

            if (!$docStep || $docStep->status !== WorkflowStatus::COMPLETE) {
                throw new Exception("Handoff ditolak: Dokumen belum lengkap atau terdapat revisi yang masih terbuka.");
            }

            // Verify Sihalal Credential
            $credential = $project->sihalalCredential()->lockForUpdate()->first();
            if (!$credential || empty($credential->email_encrypted) || empty($credential->password_encrypted)) {
                throw new Exception("Handoff ditolak: Kredensial SIHALAL belum tersedia.");
            }
            
            // Try decrypting to verify integrity
            try {
                // If it fails, an exception will be thrown by the encrypted cast
                $email = $credential->email_encrypted;
                $password = $credential->password_encrypted;
                if (!$email || !$password) {
                    throw new Exception("Kredensial kosong setelah dekripsi.");
                }
            } catch (\Exception $e) {
                throw new Exception("Handoff ditolak: Kredensial SIHALAL tidak dapat didekripsi. Validitas rusak.");
            }

            // Verify Entry PIC is assigned
            $entryAssignment = $project->assignments()
                ->where('assignment_role', AssignmentRole::ENTRY->value)
                ->whereNull('ended_at')
                ->lockForUpdate()
                ->first();

            if (!$entryAssignment || !$entryAssignment->user || $entryAssignment->user->status !== 'ACTIVE') {
                throw new Exception("Handoff ditolak: PIC Entry belum ditentukan atau tidak aktif.");
            }

            // Check if already handed over (idempotency check)
            $readinessStep = WorkflowStep::where('project_id', $project->id)
                ->where('step_code', 'ENTRY_READINESS')
                ->first();

            if ($readinessStep) {
                return; // Already handed over
            }

            // Write ENTRY_READINESS step
            $now = now();
            $readiness = WorkflowStep::create([
                'project_id' => $project->id,
                'step_code' => 'ENTRY_READINESS',
                'workflow_lane' => WorkflowLane::A->value,
                'track_code' => null,
                'status' => WorkflowStatus::COMPLETE->value,
                'is_required' => true,
                'last_changed_by' => $actor->id,
                'started_at' => $now,
                'completed_at' => $now,
            ]);

            $readiness->histories()->create([
                'project_id' => $project->id,
                'from_status' => null,
                'to_status' => WorkflowStatus::COMPLETE->value,
                'actor_id' => $actor->id,
                'metadata' => ['source' => 'HANDOVER'],
            ]);

            // Update ENTRY_PROGRESS started_at, but leave status as ENTRY_NOT_STARTED
            $entryProgress = WorkflowStep::where('project_id', $project->id)
                ->where('step_code', 'ENTRY_PROGRESS')
                ->first();

            if ($entryProgress) {
                $entryProgress->update(['started_at' => $now]);
            }

            // Create Task for Entry
            $taskKey = "PROJECT-{$project->id}:INITIAL_ENTRY_PROCESS";
            if (!Task::where('project_id', $project->id)->where('task_key', $taskKey)->exists()) {
                $task = Task::create([
                    'project_id' => $project->id,
                    'assigned_to' => $entryAssignment->user_id,
                    'assignment_role' => AssignmentRole::ENTRY->value,
                    'task_type' => TaskType::ENTRY_PROCESS->value,
                    'task_key' => $taskKey,
                    'title' => 'Review Dokumen dan Mulai Proses Entry SIHALAL',
                    'description' => 'Silakan mereview dokumen persiapan dan login ke SIHALAL untuk mulai entry data.',
                    'priority' => TaskPriority::MEDIUM->value,
                    'status' => TaskStatus::TODO->value,
                    'entered_at' => $now,
                ]);

                TaskAssignmentHistory::create([
                    'task_id' => $task->id,
                    'from_user_id' => null,
                    'to_user_id' => $entryAssignment->user_id,
                    'changed_by' => $actor->id,
                    'entered_at' => $now,
                    'reason' => 'Handoff dari Admin ke Entry',
                ]);

                app(\App\Modules\Workflows\Services\SlaManagerService::class)->startCycle($task);
            }

            // Log activity
            activity()
                ->performedOn($project)
                ->causedBy($actor)
                ->event('handover')
                ->log("Project diserahkan ke tim Entry (PIC: {$entryAssignment->user->name}).");

            // Notification will be sent via Event/Observer in real implementation, 
            // for simplicity we will send it here.
            \Filament\Notifications\Notification::make()
                ->title('Tugas Baru: Entry SIHALAL')
                ->body("Project PHC-{$project->client->business_id} telah diserahkan kepada Anda dan siap diproses di SIHALAL.")
                ->success()
                ->sendToDatabase($entryAssignment->user);
        });
    }
}
