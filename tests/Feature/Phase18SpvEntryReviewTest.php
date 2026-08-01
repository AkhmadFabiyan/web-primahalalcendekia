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
use App\Modules\Workflows\Enums\WorkflowReviewDecision;
use App\Modules\Workflows\Enums\WorkflowStatus;
use App\Modules\Workflows\Models\Task;
use App\Modules\Workflows\Models\TaskChecklistItem;
use App\Modules\Workflows\Models\WorkflowStep;
use App\Modules\Workflows\Models\WorkflowReview;
use App\Modules\Workflows\Services\EntryWorkflowService;
use App\Modules\Workflows\Services\SpvEntryWorkflowService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class Phase18SpvEntryReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // create roles
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Super Admin']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Manager Operasional']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Entry']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'SPV Entry']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Pendamping Auditor']);
    }

    private function setupProjectAndSubmit(): array
    {
        $project = Project::factory()->create([
            'project_name' => 'PHC-HAL-2024-001',
            'status' => ProjectStatus::ACTIVE,
        ]);

        $entryUser = User::factory()->create(['status' => 'ACTIVE']);
        $entryUser->assignRole('Entry');

        $spvUser = User::factory()->create(['status' => 'ACTIVE']);
        $spvUser->assignRole('SPV Entry');

        ProjectAssignment::create([
            'project_id' => $project->id,
            'user_id' => $entryUser->id,
            'assignment_role' => AssignmentRole::ENTRY->value,
            'assigned_at' => now(),
            'assigned_by' => User::factory()->create()->id,
        ]);

        ProjectAssignment::create([
            'project_id' => $project->id,
            'user_id' => $spvUser->id,
            'assignment_role' => AssignmentRole::SPV_ENTRY->value,
            'assigned_at' => now(),
            'assigned_by' => User::factory()->create()->id,
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
            'status' => WorkflowStatus::ENTRY_NOT_STARTED,
        ]);

        SihalalCredential::create([
            'project_id' => $project->id,
            'email_encrypted' => 'test@test.com',
            'password_encrypted' => 'test',
        ]);

        $task = Task::create([
            'project_id' => $project->id,
            'task_key' => "PROJECT-{$project->id}:ENTRY_PROCESS",
            'assigned_to' => $entryUser->id,
            'assignment_role' => AssignmentRole::ENTRY->value,
            'task_type' => TaskType::ENTRY_PROCESS,
            'status' => TaskStatus::TODO,
            'title' => 'Entry SIHALAL',
        ]);

        $entryService = app(EntryWorkflowService::class);
        $entryService->startEntry($task, $entryUser);

        $task->refresh();
        foreach ($task->checklistItems as $item) {
            $item->is_completed = true;
            $item->save();
        }

        $entryService->submitForReview($task, $entryUser);

        $spvTask = Task::where('project_id', $project->id)
            ->where('task_type', TaskType::SPV_ENTRY_REVIEW->value)
            ->first();

        return [$project, $entryUser, $spvUser, $task, $spvTask];
    }

    public function test_spv_can_start_review()
    {
        [$project, $entryUser, $spvUser, $entryTask, $spvTask] = $this->setupProjectAndSubmit();

        $service = app(SpvEntryWorkflowService::class);
        $service->startReview($spvTask, $spvUser);

        $spvTask->refresh();
        $this->assertEquals(TaskStatus::IN_PROGRESS, $spvTask->status);
        $this->assertNotNull($spvTask->started_at);

        $review = WorkflowReview::where('review_task_id', $spvTask->id)->first();
        $this->assertNotNull($review->started_at);
    }

    public function test_spv_can_approve()
    {
        Event::fake();

        [$project, $entryUser, $spvUser, $entryTask, $spvTask] = $this->setupProjectAndSubmit();
        $service = app(SpvEntryWorkflowService::class);
        $service->startReview($spvTask, $spvUser);

        $service->approve($spvTask, $spvUser);

        $spvTask->refresh();
        $entryTask->refresh();
        $tracker = WorkflowStep::where('project_id', $project->id)->where('step_code', 'ENTRY_PROGRESS')->first();
        $review = WorkflowReview::where('review_task_id', $spvTask->id)->first();
        $workflowA = WorkflowStep::where('project_id', $project->id)->where('step_code', 'WORKFLOW_A')->first();

        $this->assertEquals(TaskStatus::COMPLETED, $spvTask->status);
        $this->assertEquals(TaskStatus::COMPLETED, $entryTask->status);
        $this->assertEquals(WorkflowStatus::ENTRY_COMPLETED, $tracker->status);
        $this->assertEquals(WorkflowReviewDecision::APPROVED, $review->decision);
        $this->assertNotNull($workflowA);
        $this->assertEquals(WorkflowStatus::COMPLETE, $workflowA->status);

        Event::assertDispatched(\App\Events\WorkflowACompleted::class);
    }

    public function test_spv_can_request_revision()
    {
        [$project, $entryUser, $spvUser, $entryTask, $spvTask] = $this->setupProjectAndSubmit();
        $service = app(SpvEntryWorkflowService::class);
        $service->startReview($spvTask, $spvUser);

        $service->requestRevision($spvTask, $spvUser, 'Ada kesalahan input');

        $spvTask->refresh();
        $entryTask->refresh();
        $tracker = WorkflowStep::where('project_id', $project->id)->where('step_code', 'ENTRY_PROGRESS')->first();
        $review = WorkflowReview::where('review_task_id', $spvTask->id)->first();

        $this->assertEquals(TaskStatus::COMPLETED, $spvTask->status);
        $this->assertEquals(TaskStatus::REVISION, $entryTask->status);
        $this->assertNull($entryTask->completed_at); // remains null
        $this->assertEquals(WorkflowStatus::DOCUMENT_REVISION, $tracker->status);
        $this->assertEquals(WorkflowReviewDecision::REVISION_REQUESTED, $review->decision);
    }

    public function test_notify_companion_when_workflow_a_completed()
    {
        // Removed Event::fake() to allow actual listeners to execute

        [$project, $entryUser, $spvUser, $entryTask, $spvTask] = $this->setupProjectAndSubmit();
        
        $companionUser = User::factory()->create(['status' => 'ACTIVE']);
        $companionUser->assignRole('Pendamping Auditor');

        ProjectAssignment::create([
            'project_id' => $project->id,
            'user_id' => $companionUser->id,
            'assignment_role' => AssignmentRole::PENDAMPING_AUDITOR->value,
            'assigned_at' => now(),
            'assigned_by' => User::factory()->create()->id,
        ]);

        WorkflowStep::create([
            'project_id' => $project->id,
            'step_code' => 'COMPANION_PROGRESS',
            'workflow_lane' => 'B',
            'status' => WorkflowStatus::COMPANION_NOT_PROCESSED,
        ]);

        $service = app(SpvEntryWorkflowService::class);
        $service->startReview($spvTask, $spvUser);
        $service->approve($spvTask, $spvUser);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $companionUser->id,
            'notifiable_type' => User::class,
        ]);
    }
}
