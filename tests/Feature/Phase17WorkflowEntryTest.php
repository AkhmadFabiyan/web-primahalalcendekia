<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Projects\Enums\AssignmentRole;
use App\Modules\Projects\Enums\ProjectStatus;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\ProjectAssignment;
use App\Modules\Projects\Models\SihalalCredential;
use App\Modules\Workflows\Enums\TaskStatus;
use App\Modules\Workflows\Enums\TaskType;
use App\Modules\Workflows\Enums\WorkflowNoteType;
use App\Modules\Workflows\Enums\WorkflowStatus;
use App\Modules\Workflows\Models\Task;
use App\Modules\Workflows\Models\TaskChecklistItem;
use App\Modules\Workflows\Models\WorkflowStep;
use App\Modules\Workflows\Models\WorkflowHistory;
use App\Modules\Workflows\Models\WorkflowNote;
use App\Modules\Workflows\Services\EntryWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Exception;

class Phase17WorkflowEntryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed standard roles
        $roles = ['Super Admin', 'Manager Operasional', 'Admin', 'Entry', 'SPV Entry'];
        foreach ($roles as $role) {
            \Spatie\Permission\Models\Role::firstOrCreate(['name' => $role]);
        }
    }

    public function test_entry_start_task_changes_project_status_and_seeds_checklist()
    {
        $entryUser = User::factory()->create(['status' => 'ACTIVE']);
        $entryUser->assignRole('Entry');
        
        $project = Project::factory()->create(['status' => ProjectStatus::ACTIVE]);
        ProjectAssignment::create([
            'project_id' => $project->id,
            'user_id' => $entryUser->id,
            'assignment_role' => AssignmentRole::ENTRY->value,
        ]);
        
        // Documents complete
        WorkflowStep::create([
            'project_id' => $project->id,
            'step_code' => 'DOCUMENT_ADMINISTRATION',
            'workflow_lane' => 'A',
            'status' => WorkflowStatus::COMPLETE,
        ]);
        
        // Tracker exists
        WorkflowStep::create([
            'project_id' => $project->id,
            'step_code' => 'ENTRY_PROGRESS',
            'workflow_lane' => 'A',
            'track_code' => 'ENTRY',
            'status' => WorkflowStatus::ENTRY_NOT_STARTED,
        ]);
        
        // Sihalal exists
        SihalalCredential::create(['project_id' => $project->id, 'email_encrypted' => 'test@test.com', 'password_encrypted' => 'password']);
        
        $task = Task::create([
            'project_id' => $project->id,
            'assigned_to' => $entryUser->id,
            'assignment_role' => AssignmentRole::ENTRY->value,
            'task_type' => TaskType::ENTRY_PROCESS->value,
            'task_key' => 'TEST_KEY',
            'title' => 'Entry',
            'status' => TaskStatus::TODO,
        ]);

        $service = app(EntryWorkflowService::class);
        $service->startEntry($task, $entryUser);

        $task->refresh();
        $project->refresh();

        $this->assertEquals(TaskStatus::IN_PROGRESS, $task->status);
        $this->assertNotNull($task->started_at);
        $this->assertEquals(ProjectStatus::OPERATIONAL, $project->status);
        
        // Checklist seeded
        $this->assertTrue($task->checklistItems()->count() > 0);
    }

    public function test_entry_submit_to_spv_creates_review_task()
    {
        $entryUser = User::factory()->create(['status' => 'ACTIVE']);
        $entryUser->assignRole('Entry');
        
        $spvUser = User::factory()->create(['status' => 'ACTIVE']);
        $spvUser->assignRole('SPV Entry');
        
        $project = Project::factory()->create(['status' => ProjectStatus::OPERATIONAL]);
        ProjectAssignment::create([
            'project_id' => $project->id,
            'user_id' => $spvUser->id,
            'assignment_role' => AssignmentRole::SPV_ENTRY->value,
        ]);
        
        WorkflowStep::create([
            'project_id' => $project->id,
            'step_code' => 'DOCUMENT_ADMINISTRATION',
            'workflow_lane' => 'A',
            'status' => WorkflowStatus::COMPLETE,
        ]);
        
        $tracker = WorkflowStep::create([
            'project_id' => $project->id,
            'step_code' => 'ENTRY_PROGRESS',
            'workflow_lane' => 'A',
            'status' => WorkflowStatus::INPUTTING_MATERIALS_PRODUCTS,
        ]);
        
        SihalalCredential::create(['project_id' => $project->id, 'email_encrypted' => 'test@test.com', 'password_encrypted' => 'password']);
        
        $task = Task::create([
            'project_id' => $project->id,
            'assigned_to' => $entryUser->id,
            'assignment_role' => AssignmentRole::ENTRY->value,
            'task_type' => TaskType::ENTRY_PROCESS->value,
            'task_key' => 'TEST_KEY',
            'title' => 'Entry',
            'status' => TaskStatus::IN_PROGRESS,
        ]);
        
        // Seed checklist and complete it
        $checklistItem = TaskChecklistItem::create([
            'task_id' => $task->id,
            'code' => 'TEST_DOC',
            'label' => 'Test Doc',
            'is_required' => true,
            'is_completed' => true,
        ]);

        $service = app(EntryWorkflowService::class);
        $service->submitForReview($task, $entryUser);

        $task->refresh();
        $tracker->refresh();

        $this->assertEquals(TaskStatus::WAITING_REVIEW, $task->status);
        $this->assertEquals(WorkflowStatus::SUBMITTED_TO_LPH, $tracker->status);
        
        // SPV task created
        $spvTask = Task::where('project_id', $project->id)
            ->where('task_type', TaskType::SPV_ENTRY_REVIEW->value)
            ->first();
            
        $this->assertNotNull($spvTask);
        $this->assertEquals($spvUser->id, $spvTask->assigned_to);
        $this->assertEquals(TaskStatus::TODO, $spvTask->status);
    }
}
