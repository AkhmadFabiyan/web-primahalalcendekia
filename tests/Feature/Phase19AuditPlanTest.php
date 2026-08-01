<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Projects\Enums\AssignmentRole;
use App\Modules\Projects\Enums\ProjectStatus;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Services\AssignmentService;
use App\Modules\Workflows\Enums\AuditMethod;
use App\Modules\Workflows\Enums\TaskStatus;
use App\Modules\Workflows\Enums\TaskType;
use App\Modules\Workflows\Enums\WorkflowStatus;
use App\Modules\Workflows\Models\AuditPlan;
use App\Modules\Workflows\Models\Task;
use App\Modules\Workflows\Models\TaskChecklistItem;
use App\Modules\Workflows\Models\WorkflowStep;
use App\Modules\Workflows\Services\AuditPlanningService;
use App\Modules\Workflows\Services\WorkflowInitializationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class Phase19AuditPlanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->seed(\Database\Seeders\ChecklistTemplateSeeder::class);
    }

    public function test_audit_planning_flow()
    {
        $admin = User::factory()->create(['status' => 'ACTIVE']);
        $admin->assignRole('Admin');
        
        $pendamping = User::factory()->create(['status' => 'ACTIVE']);
        $pendamping->assignRole('Pendamping Auditor');

        $auditor = User::factory()->create(['status' => 'ACTIVE']);
        $auditor->assignRole('Auditor');

        $project = Project::factory()->create([
            'status' => ProjectStatus::WAITING_ACTIVATION,
        ]);

        app(WorkflowInitializationService::class)->initializeForProject($project, $admin->id);

        $project->status = ProjectStatus::ACTIVE;
        $project->save();

        $assignmentService = app(AssignmentService::class);
        $assignmentService->reassign($project, AssignmentRole::ADMIN, $admin);

        // Assign Pendamping Auditor - should trigger task creation
        $assignmentService->reassign($project, AssignmentRole::PENDAMPING_AUDITOR, $pendamping);

        $task = Task::where('project_id', $project->id)
            ->where('task_type', TaskType::AUDIT_PLANNING->value)
            ->first();
            
        $this->assertNotNull($task);
        $this->assertEquals($pendamping->id, $task->assigned_to);
        $this->assertEquals(TaskStatus::TODO, $task->status);

        $service = app(AuditPlanningService::class);
        
        // 1. Start Planning
        $service->startPlanning($task, $pendamping);
        
        $task->refresh();
        $this->assertEquals(TaskStatus::IN_PROGRESS, $task->status);
        
        $tracker = WorkflowStep::where('project_id', $project->id)->where('step_code', 'COMPANION_PROGRESS')->first();
        $this->assertEquals(WorkflowStatus::WAITING_AUDIT_SCHEDULE->value, $tracker->status->value);

        // 2. Assign Auditor
        $assignmentService->assignAuditor($project, $auditor, true);

        // 3. Save Draft
        $service->saveDraftPlan($task, $pendamping, [
            'scheduled_start_at' => now()->addDays(2),
            'scheduled_end_at' => now()->addDays(3),
            'timezone' => 'Asia/Jakarta',
            'audit_method' => AuditMethod::ONLINE->value,
            'meeting_url' => 'https://meet.google.com/xyz',
        ]);

        $tracker->refresh();
        $this->assertEquals(WorkflowStatus::AUDIT_PREPARATION->value, $tracker->status->value);

        $plan = AuditPlan::where('project_id', $project->id)->first();
        $this->assertNotNull($plan);
        $this->assertEquals(AuditMethod::ONLINE, $plan->audit_method);

        // Checklist should be generated
        $checklists = TaskChecklistItem::where('task_id', $task->id)->get();
        $this->assertNotEmpty($checklists);

        // Mark all checklists as completed
        foreach ($checklists as $cl) {
            $cl->update(['is_completed' => true]);
        }

        // 4. Confirm Schedule
        $service->confirmSchedule($task, $pendamping);

        $task->refresh();
        $this->assertEquals(TaskStatus::COMPLETED, $task->status);

        $tracker->refresh();
        $this->assertEquals(WorkflowStatus::AUDIT_SCHEDULED->value, $tracker->status->value);

        $plan->refresh();
        $this->assertNotNull($plan->confirmed_at);

        // 5. Execution task should be created
        $executionTask = Task::where('project_id', $project->id)
            ->where('task_type', TaskType::AUDIT_EXECUTION->value)
            ->first();
            
        $this->assertNotNull($executionTask);
        $this->assertEquals(TaskStatus::TODO, $executionTask->status);
        
        // 6. Reschedule
        $service->reschedule($project, $pendamping, [
            'scheduled_start_at' => now()->addDays(5),
            'scheduled_end_at' => now()->addDays(6),
        ], 'Perubahan jadwal dari klien');

        $plan->refresh();
        $this->assertEquals(now()->addDays(5)->format('Y-m-d'), $plan->scheduled_start_at->format('Y-m-d'));
        
        $executionTask->refresh();
        $this->assertEquals(now()->addDays(5)->format('Y-m-d'), $executionTask->deadline->format('Y-m-d'));
    }
}
