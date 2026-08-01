<?php

namespace App\Modules\Workflows\Services;

use App\Models\User;
use App\Modules\Projects\Enums\AssignmentRole;
use App\Modules\Projects\Enums\ProjectStatus;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\ProjectAssignment;
use App\Modules\Workflows\Enums\AuditMethod;
use App\Modules\Workflows\Enums\TaskStatus;
use App\Modules\Workflows\Enums\TaskType;
use App\Modules\Workflows\Enums\WorkflowStatus;
use App\Modules\Workflows\Models\AuditPlan;
use App\Modules\Workflows\Models\ChecklistTemplate;
use App\Modules\Workflows\Models\Task;
use App\Modules\Workflows\Models\TaskChecklistItem;
use App\Modules\Workflows\Models\WorkflowHistory;
use App\Modules\Workflows\Models\WorkflowStep;
use Exception;
use Illuminate\Support\Facades\DB;

class AuditPlanningService
{
    public function ensureAuditPlanningTask(Project $project): void
    {
        DB::transaction(function () use ($project) {
            $project = Project::where('id', $project->id)->lockForUpdate()->firstOrFail();

            if (!in_array($project->status, [ProjectStatus::ACTIVE, ProjectStatus::OPERATIONAL])) {
                return;
            }

            $assignment = $project->assignments()
                ->where('assignment_role', AssignmentRole::PENDAMPING_AUDITOR->value)
                ->whereNull('ended_at')
                ->first();

            if (!$assignment || !$assignment->user || $assignment->user->status !== 'ACTIVE') {
                return;
            }

            $taskKey = "PROJECT-{$project->id}:AUDIT_PLANNING";
            $task = Task::firstOrCreate(
                ['project_id' => $project->id, 'task_key' => $taskKey],
                [
                    'assigned_to' => $assignment->user_id,
                    'assignment_role' => AssignmentRole::PENDAMPING_AUDITOR->value,
                    'task_type' => TaskType::AUDIT_PLANNING->value,
                    'title' => 'Perencanaan Audit',
                    'status' => TaskStatus::TODO,
                    'priority' => 'HIGH',
                    'entered_at' => now(),
                ]
            );

            app(\App\Modules\Workflows\Services\SlaManagerService::class)->startCycle($task);
        });
    }

    public function startPlanning(Task $task, User $actor): void
    {
        DB::transaction(function () use ($task, $actor) {
            $project = Project::where('id', $task->project_id)->lockForUpdate()->firstOrFail();
            $task = Task::where('id', $task->id)->lockForUpdate()->firstOrFail();

            if ($task->assigned_to !== $actor->id) {
                throw new Exception("Anda tidak memiliki akses untuk memulai tugas ini.");
            }

            if ($task->status !== TaskStatus::TODO) {
                throw new Exception("Tugas tidak dapat dimulai karena status saat ini adalah " . $task->status->value);
            }

            $task->status = TaskStatus::IN_PROGRESS;
            if (!$task->started_at) {
                $task->started_at = now();
            }
            $task->save();

            $tracker = WorkflowStep::where('project_id', $project->id)
                ->where('step_code', 'COMPANION_PROGRESS')
                ->lockForUpdate()
                ->firstOrFail();

            if ($tracker->status === WorkflowStatus::COMPANION_NOT_PROCESSED) {
                $oldStatus = $tracker->status->value;
                $tracker->status = WorkflowStatus::WAITING_AUDIT_SCHEDULE;
                $tracker->last_changed_by = $actor->id;
                $tracker->save();

                WorkflowHistory::create([
                    'project_id' => $project->id,
                    'workflow_step_id' => $tracker->id,
                    'from_status' => $oldStatus,
                    'to_status' => WorkflowStatus::WAITING_AUDIT_SCHEDULE->value,
                    'actor_id' => $actor->id,
                ]);
            }

            if ($project->status === ProjectStatus::ACTIVE) {
                $project->status = ProjectStatus::OPERATIONAL;
                $project->save();
            }

            activity()
                ->performedOn($task)
                ->causedBy($actor)
                ->event('started')
                ->log("Mulai Perencanaan Audit");
        });
    }

    public function saveDraftPlan(Task $task, User $actor, array $data): void
    {
        DB::transaction(function () use ($task, $actor, $data) {
            $project = Project::where('id', $task->project_id)->lockForUpdate()->firstOrFail();
            $task = Task::where('id', $task->id)->lockForUpdate()->firstOrFail();
            $tracker = WorkflowStep::where('project_id', $project->id)
                ->where('step_code', 'COMPANION_PROGRESS')
                ->lockForUpdate()
                ->firstOrFail();

            if ($task->assigned_to !== $actor->id) {
                throw new Exception("Anda tidak memiliki akses untuk memperbarui tugas ini.");
            }

            if ($task->status !== TaskStatus::IN_PROGRESS) {
                throw new Exception("Tugas tidak dalam status IN_PROGRESS.");
            }

            $methodEnum = isset($data['audit_method']) ? AuditMethod::tryFrom($data['audit_method']) : null;

            $plan = AuditPlan::firstOrCreate(
                ['project_id' => $project->id]
            );

            $oldMethod = $plan->audit_method;

            $plan->scheduled_start_at = $data['scheduled_start_at'] ?? $plan->scheduled_start_at;
            $plan->scheduled_end_at = $data['scheduled_end_at'] ?? $plan->scheduled_end_at;
            $plan->timezone = $data['timezone'] ?? $plan->timezone;
            $plan->audit_method = $methodEnum ?? $plan->audit_method;
            $plan->location = $methodEnum === AuditMethod::ONSITE ? ($data['location'] ?? $plan->location) : null;
            $plan->meeting_url = $methodEnum === AuditMethod::ONLINE ? ($data['meeting_url'] ?? $plan->meeting_url) : null;
            $plan->notes = $data['notes'] ?? $plan->notes;
            $plan->updated_by = $actor->id;
            
            if ($plan->wasRecentlyCreated) {
                $plan->scheduled_by = $actor->id;
            }
            
            $plan->save();

            // Refresh checklists if method changed and not confirmed/progressing
            if ($oldMethod !== $plan->audit_method && $plan->audit_method) {
                $canChangeChecklist = true;
                if ($tracker->status->value !== WorkflowStatus::WAITING_AUDIT_SCHEDULE->value && 
                    $tracker->status->value !== WorkflowStatus::AUDIT_PREPARATION->value &&
                    $tracker->status->value !== WorkflowStatus::COMPANION_NOT_PROCESSED->value) {
                    $canChangeChecklist = false; // Plan is confirmed or audit in progress
                }
                
                $hasProgress = TaskChecklistItem::where('task_id', $task->id)->where('is_completed', true)->exists();
                if ($hasProgress) {
                    $canChangeChecklist = false;
                }

                if ($canChangeChecklist) {
                    TaskChecklistItem::where('task_id', $task->id)->delete();
                    
                    $templateCode = $plan->audit_method === AuditMethod::ONLINE 
                        ? 'AUDIT_PLANNING_ONLINE' 
                        : 'AUDIT_PLANNING_ONSITE';
                        
                    $template = ChecklistTemplate::where('code', $templateCode)->with('items')->first();
                    if ($template) {
                        foreach ($template->items as $item) {
                            TaskChecklistItem::create([
                                'task_id' => $task->id,
                                'code' => $item->code,
                                'label' => $item->label,
                                'is_required' => $item->is_required,
                                'sort_order' => $item->sort_order,
                                'is_completed' => false,
                            ]);
                        }
                    }
                }
            }

            if ($tracker->status === WorkflowStatus::WAITING_AUDIT_SCHEDULE) {
                $oldStatus = $tracker->status->value;
                $tracker->status = WorkflowStatus::AUDIT_PREPARATION;
                $tracker->last_changed_by = $actor->id;
                $tracker->save();

                WorkflowHistory::create([
                    'project_id' => $project->id,
                    'workflow_step_id' => $tracker->id,
                    'from_status' => $oldStatus,
                    'to_status' => WorkflowStatus::AUDIT_PREPARATION->value,
                    'actor_id' => $actor->id,
                ]);
            }
        });
    }

    public function confirmSchedule(Task $task, User $actor): void
    {
        $notifications = [];
        
        DB::transaction(function () use ($task, $actor, &$notifications) {
            $project = Project::where('id', $task->project_id)->lockForUpdate()->firstOrFail();
            $task = Task::where('id', $task->id)->lockForUpdate()->firstOrFail();
            $tracker = WorkflowStep::where('project_id', $project->id)
                ->where('step_code', 'COMPANION_PROGRESS')
                ->lockForUpdate()
                ->firstOrFail();
            $plan = AuditPlan::where('project_id', $project->id)->lockForUpdate()->first();

            if ($task->assigned_to !== $actor->id) {
                throw new Exception("Anda tidak memiliki akses untuk konfirmasi jadwal.");
            }

            if ($task->status !== TaskStatus::IN_PROGRESS) {
                throw new Exception("Task belum IN_PROGRESS.");
            }

            if (!$plan) {
                throw new Exception("Draft rencana audit belum diisi.");
            }

            if (!$plan->scheduled_start_at || !$plan->scheduled_end_at || !$plan->audit_method) {
                throw new Exception("Tanggal mulai, tanggal selesai, dan metode audit wajib diisi.");
            }

            if ($plan->scheduled_start_at >= $plan->scheduled_end_at) {
                throw new Exception("Tanggal selesai harus lebih besar dari tanggal mulai.");
            }

            if ($plan->audit_method === AuditMethod::ONLINE && empty($plan->meeting_url)) {
                throw new Exception("Link pertemuan wajib diisi untuk audit Online.");
            }

            if ($plan->audit_method === AuditMethod::ONSITE && empty($plan->location)) {
                throw new Exception("Lokasi fisik wajib diisi untuk audit Onsite.");
            }

            $primaryAuditor = ProjectAssignment::where('project_id', $project->id)
                ->where('assignment_role', AssignmentRole::AUDITOR->value)
                ->where('is_primary', true)
                ->whereNull('ended_at')
                ->lockForUpdate()
                ->first();

            if (!$primaryAuditor) {
                throw new Exception("Auditor Utama belum ditentukan atau tidak aktif.");
            }

            $uncompletedChecklists = TaskChecklistItem::where('task_id', $task->id)
                ->where('is_required', true)
                ->where('is_completed', false)
                ->exists();

            if ($uncompletedChecklists) {
                throw new Exception("Seluruh checklist persiapan audit wajib diselesaikan sebelum konfirmasi.");
            }

            // Mark Confirmed
            $plan->confirmed_at = now();
            $plan->confirmed_by = $actor->id;
            $plan->save();

            // Update Tracker
            $oldStatus = $tracker->status->value;
            $tracker->status = WorkflowStatus::AUDIT_SCHEDULED;
            $tracker->last_changed_by = $actor->id;
            $tracker->save();

            WorkflowHistory::create([
                'project_id' => $project->id,
                'workflow_step_id' => $tracker->id,
                'from_status' => $oldStatus,
                'to_status' => WorkflowStatus::AUDIT_SCHEDULED->value,
                'actor_id' => $actor->id,
            ]);

            // Complete Planning Task
            $task->status = TaskStatus::COMPLETED;
            $task->completed_at = now();
            $task->save();

            // Activity Log
            activity()
                ->performedOn($plan)
                ->causedBy($actor)
                ->event('confirmed')
                ->log("Mengonfirmasi Jadwal Audit");

            // Create Execution Task
            $executionTaskKey = "PROJECT-{$project->id}:AUDIT_EXECUTION";
            $executionTask = Task::firstOrCreate(
                ['project_id' => $project->id, 'task_key' => $executionTaskKey],
                [
                    'assigned_to' => $actor->id,
                    'assignment_role' => AssignmentRole::PENDAMPING_AUDITOR->value,
                    'task_type' => TaskType::AUDIT_EXECUTION->value,
                    'title' => 'Pelaksanaan Audit Lapangan',
                    'status' => TaskStatus::TODO,
                    'priority' => 'HIGH',
                    'entered_at' => now(),
                    'deadline' => $plan->scheduled_start_at,
                ]
            );

            app(\App\Modules\Workflows\Services\SlaManagerService::class)->startCycle($executionTask);

            // Notify Auditors (prepare list)
            $auditors = ProjectAssignment::where('project_id', $project->id)
                ->where('assignment_role', AssignmentRole::AUDITOR->value)
                ->whereNull('ended_at')
                ->with('user')
                ->get();

            foreach ($auditors as $auditorAssign) {
                if ($auditorAssign->user && $auditorAssign->user->status === 'ACTIVE') {
                    $notifications[] = [
                        'user' => $auditorAssign->user,
                        'project_name' => $project->project_name,
                        'time' => $plan->scheduled_start_at->format('d M Y H:i')
                    ];
                }
            }
        });
        
        // Send notifications outside transaction
        foreach ($notifications as $notif) {
            \Filament\Notifications\Notification::make()
                ->title('Audit Dijadwalkan')
                ->body("Audit {$notif['project_name']} telah dijadwalkan pada {$notif['time']}.")
                ->success()
                ->sendToDatabase($notif['user']);
        }
    }

    public function reschedule(Project $project, User $actor, array $data, string $reason): void
    {
        $notifications = [];
        
        DB::transaction(function () use ($project, $actor, $data, $reason, &$notifications) {
            $project = Project::where('id', $project->id)->lockForUpdate()->firstOrFail();
            $plan = AuditPlan::where('project_id', $project->id)->lockForUpdate()->firstOrFail();
            $tracker = WorkflowStep::where('project_id', $project->id)
                ->where('step_code', 'COMPANION_PROGRESS')
                ->lockForUpdate()
                ->firstOrFail();

            if ($tracker->status->value !== WorkflowStatus::AUDIT_SCHEDULED->value && $tracker->status->value !== WorkflowStatus::AUDIT_PREPARATION->value && $tracker->status->value !== WorkflowStatus::WAITING_AUDIT_SCHEDULE->value) {
                throw new Exception("Audit yang sudah berjalan tidak dapat dijadwalkan ulang secara reguler.");
            }

            if (empty($reason)) {
                throw new Exception("Alasan perubahan jadwal wajib diisi.");
            }

            $assignment = $project->assignments()
                ->where('assignment_role', AssignmentRole::PENDAMPING_AUDITOR->value)
                ->where('user_id', $actor->id)
                ->whereNull('ended_at')
                ->first();

            if (!$assignment) {
                throw new Exception("Anda bukan Pendamping aktif untuk Project ini.");
            }

            $methodEnum = isset($data['audit_method']) ? AuditMethod::tryFrom($data['audit_method']) : $plan->audit_method;

            $oldData = [
                'scheduled_start_at' => $plan->scheduled_start_at,
                'scheduled_end_at' => $plan->scheduled_end_at,
                'audit_method' => $plan->audit_method,
                'location' => $plan->location,
                'meeting_url' => $plan->meeting_url,
            ];

            $plan->scheduled_start_at = $data['scheduled_start_at'] ?? $plan->scheduled_start_at;
            $plan->scheduled_end_at = $data['scheduled_end_at'] ?? $plan->scheduled_end_at;
            $plan->timezone = $data['timezone'] ?? $plan->timezone;
            $plan->audit_method = $methodEnum;
            $plan->location = $methodEnum === AuditMethod::ONSITE ? ($data['location'] ?? $plan->location) : null;
            $plan->meeting_url = $methodEnum === AuditMethod::ONLINE ? ($data['meeting_url'] ?? $plan->meeting_url) : null;
            $plan->notes = $data['notes'] ?? $plan->notes;
            $plan->updated_by = $actor->id;

            if ($plan->scheduled_start_at >= $plan->scheduled_end_at) {
                throw new Exception("Tanggal selesai harus lebih besar dari tanggal mulai.");
            }

            if ($plan->audit_method === AuditMethod::ONLINE && empty($plan->meeting_url)) {
                throw new Exception("Link pertemuan wajib diisi untuk audit Online.");
            }

            if ($plan->audit_method === AuditMethod::ONSITE && empty($plan->location)) {
                throw new Exception("Lokasi fisik wajib diisi untuk audit Onsite.");
            }

            $plan->save();

            // Update execution task deadline if exists
            $executionTask = Task::where('project_id', $project->id)
                ->where('task_type', TaskType::AUDIT_EXECUTION->value)
                ->lockForUpdate()
                ->first();

            if ($executionTask && $executionTask->status !== TaskStatus::COMPLETED) {
                $executionTask->deadline = $plan->scheduled_start_at;
                $executionTask->save();
            }

            // Activity Log manually
            activity()
                ->performedOn($plan)
                ->causedBy($actor)
                ->event('rescheduled')
                ->withProperties([
                    'old' => $oldData,
                    'attributes' => [
                        'scheduled_start_at' => $plan->scheduled_start_at,
                        'scheduled_end_at' => $plan->scheduled_end_at,
                        'audit_method' => $plan->audit_method,
                        'location' => $plan->location,
                        'meeting_url' => $plan->meeting_url,
                    ],
                    'reason' => $reason
                ])
                ->log("Mengubah Jadwal Audit");

            // Notify Auditors
            $auditors = ProjectAssignment::where('project_id', $project->id)
                ->where('assignment_role', AssignmentRole::AUDITOR->value)
                ->whereNull('ended_at')
                ->with('user')
                ->get();

            foreach ($auditors as $auditorAssign) {
                if ($auditorAssign->user && $auditorAssign->user->status === 'ACTIVE') {
                    $notifications[] = [
                        'user' => $auditorAssign->user,
                        'project_name' => $project->project_name,
                        'time' => $plan->scheduled_start_at->format('d M Y H:i')
                    ];
                }
            }
        });
        
        // Send notifications outside transaction
        foreach ($notifications as $notif) {
            \Filament\Notifications\Notification::make()
                ->title('Jadwal Audit Diperbarui')
                ->body("Jadwal audit {$notif['project_name']} telah diperbarui menjadi {$notif['time']}.")
                ->success()
                ->sendToDatabase($notif['user']);
        }
    }
}
