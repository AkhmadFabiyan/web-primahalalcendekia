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
use App\Modules\Workflows\Enums\WorkflowStatus;
use App\Modules\Workflows\Models\AuditExecution;
use App\Modules\Workflows\Models\AuditFinding;
use App\Modules\Workflows\Models\AuditPlan;
use App\Modules\Workflows\Models\ChecklistTemplate;
use App\Modules\Workflows\Models\ChecklistTemplateItem;
use App\Modules\Workflows\Models\Task;
use App\Modules\Workflows\Models\TaskChecklistItem;
use App\Modules\Workflows\Models\WorkflowStep;
use App\Modules\Workflows\Services\AuditExecutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class Phase20AuditExecutionTest extends TestCase
{
    use RefreshDatabase;

    protected User $pendamping;
    protected User $auditor;
    protected Project $project;
    protected Task $executionTask;
    protected AuditPlan $auditPlan;
    protected AuditExecutionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Pendamping Auditor']);
        Role::firstOrCreate(['name' => 'Auditor']);

        $this->pendamping = User::factory()->create(['status' => 'ACTIVE']);
        $this->pendamping->assignRole('Pendamping Auditor');

        $this->auditor = User::factory()->create(['status' => 'ACTIVE']);
        $this->auditor->assignRole('Auditor');

        $this->project = Project::factory()->create([
            'status' => ProjectStatus::ACTIVE,
        ]);

        ProjectAssignment::create([
            'project_id' => $this->project->id,
            'user_id' => $this->pendamping->id,
            'assignment_role' => AssignmentRole::PENDAMPING_AUDITOR->value,
        ]);

        ProjectAssignment::create([
            'project_id' => $this->project->id,
            'user_id' => $this->auditor->id,
            'assignment_role' => AssignmentRole::AUDITOR->value,
            'is_primary' => true,
        ]);

        WorkflowStep::create([
            'project_id' => $this->project->id,
            'step_code' => 'COMPANION_PROGRESS',
            'status' => WorkflowStatus::AUDIT_SCHEDULED->value,
            'workflow_lane' => 'B',
        ]);
        
        WorkflowStep::create([
            'project_id' => $this->project->id,
            'step_code' => 'AUDITOR_REVIEW',
            'status' => WorkflowStatus::AUDITOR_NOT_PROCESSED->value,
            'workflow_lane' => 'B',
        ]);

        $this->executionTask = Task::create([
            'project_id' => $this->project->id,
            'task_key' => 'PROJECT-' . $this->project->id . ':AUDIT_EXECUTION',
            'task_type' => TaskType::AUDIT_EXECUTION->value,
            'title' => 'Pelaksanaan Audit',
            'assigned_to' => $this->pendamping->id,
            'assignment_role' => AssignmentRole::PENDAMPING_AUDITOR->value,
            'status' => TaskStatus::TODO,
            'priority' => 'HIGH',
            'entered_at' => now(),
        ]);

        $this->auditPlan = AuditPlan::create([
            'project_id' => $this->project->id,
            'audit_method' => 'ONLINE',
            'scheduled_start_at' => now()->addDays(2),
            'scheduled_end_at' => now()->addDays(2)->addHours(4),
            'timezone' => 'Asia/Jakarta',
            'confirmed_at' => now(),
            'confirmed_by' => $this->pendamping->id,
        ]);

        $template = ChecklistTemplate::create([
            'code' => 'AUDIT_EXECUTION_ONLINE',
            'task_type' => TaskType::AUDIT_EXECUTION->value,
            'context' => 'Audit Execution Online',
            'is_active' => true,
        ]);

        ChecklistTemplateItem::create([
            'checklist_template_id' => $template->id,
            'code' => 'TEST_ITEM_1',
            'label' => 'Item 1',
            'is_required' => true,
            'sort_order' => 1,
        ]);

        $this->service = app(AuditExecutionService::class);
    }

    public function test_can_start_execution_and_copies_checklists()
    {
        $this->service->startExecution($this->executionTask, $this->pendamping);

        $this->assertDatabaseHas('audit_executions', [
            'project_id' => $this->project->id,
            'audit_plan_id' => $this->auditPlan->id,
            'task_id' => $this->executionTask->id,
            'started_by' => $this->pendamping->id,
        ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $this->executionTask->id,
            'status' => TaskStatus::IN_PROGRESS->value,
        ]);

        $this->assertDatabaseHas('workflow_steps', [
            'project_id' => $this->project->id,
            'step_code' => 'COMPANION_PROGRESS',
            'status' => WorkflowStatus::AUDIT_IN_PROGRESS->value,
        ]);

        $this->assertDatabaseHas('projects', [
            'id' => $this->project->id,
            'status' => ProjectStatus::OPERATIONAL->value,
        ]);

        $this->assertDatabaseHas('task_checklist_items', [
            'task_id' => $this->executionTask->id,
            'code' => 'TEST_ITEM_1',
        ]);
    }

    public function test_can_update_companion_status()
    {
        $this->service->updateCompanionStatus(
            $this->project,
            $this->pendamping,
            WorkflowStatus::FIELD_EVIDENCE_INCOMPLETE,
            'Bukti kurang lengkap'
        );

        $this->assertDatabaseHas('workflow_steps', [
            'project_id' => $this->project->id,
            'step_code' => 'COMPANION_PROGRESS',
            'status' => WorkflowStatus::FIELD_EVIDENCE_INCOMPLETE->value,
        ]);

        $this->assertDatabaseHas('workflow_histories', [
            'project_id' => $this->project->id,
            'to_status' => WorkflowStatus::FIELD_EVIDENCE_INCOMPLETE->value,
        ]);
    }

    public function test_can_add_update_and_void_finding()
    {
        $this->service->startExecution($this->executionTask, $this->pendamping);
        $this->executionTask->refresh();

        $finding = $this->service->addFinding($this->executionTask, $this->pendamping, [
            'description' => 'Temuan Awal',
            'evidence_required' => true,
        ]);

        $this->assertDatabaseHas('audit_findings', [
            'id' => $finding->id,
            'description' => 'Temuan Awal',
            'status' => AuditFindingStatus::OPEN->value,
            'evidence_required' => 1,
        ]);

        $this->service->updateFinding($finding, $this->pendamping, [
            'description' => 'Temuan Diupdate',
            'evidence_required' => false,
        ]);

        $this->assertDatabaseHas('audit_findings', [
            'id' => $finding->id,
            'description' => 'Temuan Diupdate',
            'evidence_required' => 0,
        ]);

        $this->service->voidFinding($finding, $this->pendamping, 'Salah input');

        $this->assertDatabaseHas('audit_findings', [
            'id' => $finding->id,
            'status' => AuditFindingStatus::VOIDED->value,
            'resolution_notes' => 'Salah input',
        ]);
    }

    public function test_cannot_submit_if_checklist_incomplete()
    {
        $this->service->startExecution($this->executionTask, $this->pendamping);
        $this->executionTask->refresh();

        $this->expectExceptionMessage("Seluruh checklist pelaksanaan audit wajib diselesaikan.");

        $this->service->submitToAuditor($this->executionTask, $this->pendamping, [
            'summary' => 'Selesai',
            'has_findings' => false,
        ]);
    }

    public function test_can_submit_to_auditor()
    {
        $this->service->startExecution($this->executionTask, $this->pendamping);
        $this->executionTask->refresh();

        TaskChecklistItem::where('task_id', $this->executionTask->id)->update([
            'is_completed' => true,
            'completed_by' => $this->pendamping->id,
            'completed_at' => now(),
        ]);

        $this->service->submitToAuditor($this->executionTask, $this->pendamping, [
            'summary' => 'Audit selesai, tidak ada temuan.',
            'has_findings' => false,
        ]);

        $this->assertDatabaseHas('audit_executions', [
            'task_id' => $this->executionTask->id,
            'summary' => 'Audit selesai, tidak ada temuan.',
            'has_findings' => 0,
            'submitted_by' => $this->pendamping->id,
        ]);

        $this->assertDatabaseHas('workflow_steps', [
            'project_id' => $this->project->id,
            'step_code' => 'COMPANION_PROGRESS',
            'status' => WorkflowStatus::ASSISTANCE_COMPLETED->value,
        ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $this->executionTask->id,
            'status' => TaskStatus::WAITING_REVIEW->value,
        ]);

        // Review task created
        $this->assertDatabaseHas('tasks', [
            'project_id' => $this->project->id,
            'task_type' => TaskType::AUDITOR_REVIEW->value,
            'status' => TaskStatus::TODO->value,
            'assigned_to' => $this->auditor->id,
        ]);
    }

    public function test_cannot_submit_without_evidence_if_required()
    {
        Storage::fake('public');

        $this->service->startExecution($this->executionTask, $this->pendamping);
        $this->executionTask->refresh();

        TaskChecklistItem::where('task_id', $this->executionTask->id)->update([
            'is_completed' => true,
        ]);

        $finding = $this->service->addFinding($this->executionTask, $this->pendamping, [
            'description' => 'Harus ada bukti',
            'evidence_required' => true,
        ]);

        $this->expectExceptionMessage("Temuan FIND-001 mewajibkan bukti, tetapi belum ada file yang diunggah.");

        $this->service->submitToAuditor($this->executionTask, $this->pendamping, [
            'summary' => 'Ada temuan',
            'has_findings' => true,
        ]);
    }
}
