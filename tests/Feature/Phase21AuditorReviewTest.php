<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Projects\Enums\AssignmentRole;
use App\Modules\Projects\Enums\ProjectStatus;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\ProjectAssignment;
use App\Modules\Workflows\Enums\AuditFindingStatus;
use App\Modules\Workflows\Enums\TaskStatus;
use App\Modules\Workflows\Enums\TaskType;
use App\Modules\Workflows\Enums\WorkflowLane;
use App\Modules\Workflows\Enums\WorkflowStatus;
use App\Modules\Workflows\Enums\WorkflowTrack;
use App\Modules\Workflows\Models\AuditExecution;
use App\Modules\Workflows\Models\AuditFinding;
use App\Modules\Workflows\Models\Task;
use App\Modules\Workflows\Models\TaskChecklistItem;
use App\Modules\Workflows\Models\WorkflowStep;
use App\Modules\Workflows\Services\AuditExecutionService;
use App\Modules\Workflows\Services\AuditorReviewService;
use App\Modules\Workflows\Services\WorkflowInitializationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class Phase21AuditorReviewTest extends TestCase
{
    use RefreshDatabase;

    protected AuditorReviewService $reviewService;
    protected AuditExecutionService $executionService;
    protected User $pendamping;
    protected User $auditor;
    protected Project $project;
    protected Task $executionTask;
    protected Task $reviewTask;
    protected AuditExecution $execution;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reviewService = app(AuditorReviewService::class);
        $this->executionService = app(AuditExecutionService::class);

        $this->pendamping = User::factory()->create(['status' => 'ACTIVE']);
        $this->auditor = User::factory()->create(['status' => 'ACTIVE']);

        $this->project = Project::factory()->create(['status' => ProjectStatus::OPERATIONAL]);

        ProjectAssignment::create([
            'project_id' => $this->project->id,
            'user_id' => $this->pendamping->id,
            'assignment_role' => AssignmentRole::PENDAMPING_AUDITOR->value,
            'is_primary' => true,
        ]);

        ProjectAssignment::create([
            'project_id' => $this->project->id,
            'user_id' => $this->auditor->id,
            'assignment_role' => AssignmentRole::AUDITOR->value,
            'is_primary' => true,
        ]);

        app(WorkflowInitializationService::class)->initializeForProject($this->project, $this->pendamping->id);

        $this->executionTask = Task::create([
            'project_id' => $this->project->id,
            'task_key' => "PROJECT-{$this->project->id}:AUDIT_EXECUTION",
            'task_type' => TaskType::AUDIT_EXECUTION->value,
            'title' => 'Pelaksanaan Audit',
            'assigned_to' => $this->pendamping->id,
            'assignment_role' => AssignmentRole::PENDAMPING_AUDITOR->value,
            'status' => TaskStatus::TODO,
            'priority' => 'HIGH',
            'entered_at' => now(),
        ]);

        \App\Modules\Workflows\Models\AuditPlan::create([
            'project_id' => $this->project->id,
            'scheduled_start_at' => now()->addDays(2),
            'location' => 'Kantor Pusat',
            'audit_method' => \App\Modules\Workflows\Enums\AuditMethod::ONSITE->value,
            'confirmed_at' => now(),
            'confirmed_by' => $this->pendamping->id,
        ]);

        // Simulasikan Pendamping melakukan eksekusi dan submit
        $this->executionService->startExecution($this->executionTask, $this->pendamping);
        
        TaskChecklistItem::where('task_id', $this->executionTask->id)->update([
            'is_completed' => true,
        ]);

        $this->executionService->submitToAuditor($this->executionTask, $this->pendamping, [
            'summary' => 'Pelaksanaan audit selesai',
            'has_findings' => false,
        ]);

        $this->executionTask->refresh();
        $this->reviewTask = Task::where('parent_task_id', $this->executionTask->id)
            ->where('task_type', TaskType::AUDITOR_REVIEW->value)
            ->firstOrFail();
    }

    public function test_auditor_can_start_review()
    {
        $this->reviewService->startReview($this->reviewTask, $this->auditor);
        
        $this->reviewTask->refresh();
        $this->assertEquals(TaskStatus::IN_PROGRESS, $this->reviewTask->status);

        $this->assertDatabaseHas('workflow_steps', [
            'project_id' => $this->project->id,
            'step_code' => 'AUDITOR_PROGRESS',
            'status' => WorkflowStatus::DOCUMENT_REVIEW->value,
        ]);
    }

    public function test_auditor_can_approve_execution()
    {
        $this->reviewService->startReview($this->reviewTask, $this->auditor);
        $this->reviewTask->refresh();

        $this->reviewService->approveExecution($this->reviewTask, $this->auditor);

        $this->reviewTask->refresh();
        $this->executionTask->refresh();

        $this->assertEquals(TaskStatus::COMPLETED, $this->reviewTask->status);
        $this->assertEquals(TaskStatus::COMPLETED, $this->executionTask->status);

        $this->assertDatabaseHas('workflow_reviews', [
            'review_task_id' => $this->reviewTask->id,
            'decision' => \App\Modules\Workflows\Enums\WorkflowReviewDecision::APPROVED->value,
        ]);

        $this->assertDatabaseHas('workflow_steps', [
            'project_id' => $this->project->id,
            'step_code' => 'AUDITOR_PROGRESS',
            'status' => WorkflowStatus::FIELD_AUDIT_COMPLETED->value,
        ]);
    }

    public function test_auditor_can_request_revision()
    {
        $this->reviewService->startReview($this->reviewTask, $this->auditor);
        $this->reviewTask->refresh();

        $this->reviewService->requestRevision($this->reviewTask, $this->auditor, 'Bukti kurang lengkap');

        $this->reviewTask->refresh();
        $this->executionTask->refresh();

        $this->assertEquals(TaskStatus::COMPLETED, $this->reviewTask->status);
        $this->assertEquals(TaskStatus::IN_PROGRESS, $this->executionTask->status); // Kembali terbuka untuk pendamping

        $this->assertDatabaseHas('workflow_reviews', [
            'review_task_id' => $this->reviewTask->id,
            'decision' => \App\Modules\Workflows\Enums\WorkflowReviewDecision::REVISION_REQUESTED->value,
            'reason' => 'Bukti kurang lengkap',
        ]);

        $this->assertDatabaseHas('workflow_steps', [
            'project_id' => $this->project->id,
            'step_code' => 'AUDITOR_PROGRESS',
            'status' => WorkflowStatus::NONCONFORMITY_FOUND->value,
        ]);

        $this->assertDatabaseHas('workflow_steps', [
            'project_id' => $this->project->id,
            'step_code' => 'COMPANION_PROGRESS',
            'status' => WorkflowStatus::WAITING_CLIENT_CORRECTION->value,
        ]);
    }

    public function test_workflow_b_completed_event_is_dispatched()
    {
        Event::fake([
            \App\Events\WorkflowBCompleted::class,
        ]);

        $this->reviewService->updateAuditorStatus($this->project, $this->auditor, WorkflowStatus::FATWA_SESSION_COMPLETED, 'Fatwa selesai');

        Event::assertDispatched(\App\Events\WorkflowBCompleted::class, function ($event) {
            return $event->projectId === $this->project->id;
        });
    }

    public function test_project_status_sync_waiting_government_invoice()
    {
        // Set Workflow A to ENTRY_COMPLETED
        WorkflowStep::where('project_id', $this->project->id)->where('step_code', 'ENTRY_PROGRESS')->update([
            'status' => WorkflowStatus::ENTRY_COMPLETED->value,
        ]);

        // Mock event WorkflowBCompleted
        $event = new \App\Events\WorkflowBCompleted($this->project->id);
        
        $listener = new \App\Listeners\CheckWorkflowCompletionListener();
        
        // Before handling, update workflow B to FATWA_SESSION_COMPLETED
        WorkflowStep::where('project_id', $this->project->id)->where('step_code', 'AUDITOR_PROGRESS')->update([
            'status' => WorkflowStatus::FATWA_SESSION_COMPLETED->value,
        ]);

        $listener->handle($event);

        $this->project->refresh();
        
        $this->assertEquals(ProjectStatus::WAITING_GOVERNMENT_INVOICE, $this->project->status);
        
        $this->assertDatabaseHas('activity_log', [
            'subject_id' => $this->project->id,
            'subject_type' => get_class($this->project),
            'event' => 'project_status_updated',
        ]);
    }
}
