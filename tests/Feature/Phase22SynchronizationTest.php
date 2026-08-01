<?php

namespace Tests\Feature;

use App\Events\WorkflowACompleted;
use App\Events\WorkflowBCompleted;
use App\Models\User;
use App\Modules\Projects\Enums\ProjectStatus;
use App\Modules\Projects\Models\Project;
use App\Modules\Workflows\Enums\WorkflowStatus;
use App\Modules\Workflows\Models\Task;
use App\Modules\Workflows\Models\WorkflowStep;
use App\Modules\Workflows\Services\EntryWorkflowService;
use App\Modules\Workflows\Services\WorkflowReopeningService;
use App\Modules\Workflows\Services\WorkflowProgressService;
use App\Modules\Payments\Services\GovernmentInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Role;
use Exception;

class Phase22SynchronizationTest extends TestCase
{
    use RefreshDatabase;

    protected Project $project;
    protected User $admin;
    protected User $user;
    protected WorkflowStep $stepA;
    protected WorkflowStep $stepB;
    protected WorkflowReopeningService $reopenService;
    protected GovernmentInvoiceService $invoiceService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Super Admin');

        $this->user = User::factory()->create();

        $this->project = Project::factory()->create([
            'status' => ProjectStatus::OPERATIONAL,
        ]);

        $this->stepA = WorkflowStep::create([
            'project_id' => $this->project->id,
            'step_code' => 'ENTRY_PROGRESS',
            'workflow_lane' => \App\Modules\Workflows\Enums\WorkflowLane::A->value,
            'status' => WorkflowStatus::ENTRY_NOT_STARTED,
        ]);

        $this->stepB = WorkflowStep::create([
            'project_id' => $this->project->id,
            'step_code' => 'AUDITOR_PROGRESS',
            'workflow_lane' => \App\Modules\Workflows\Enums\WorkflowLane::B->value,
            'status' => WorkflowStatus::AUDITOR_NOT_PROCESSED,
        ]);

        $this->reopenService = app(WorkflowReopeningService::class);
        $this->invoiceService = app(GovernmentInvoiceService::class);

        // Register listeners for test
        \Illuminate\Support\Facades\Event::listen(
            WorkflowACompleted::class,
            \App\Listeners\CheckWorkflowCompletionListener::class
        );
        \Illuminate\Support\Facades\Event::listen(
            WorkflowBCompleted::class,
            \App\Listeners\CheckWorkflowCompletionListener::class
        );
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\WorkflowStatusReverted::class,
            \App\Listeners\WorkflowRevertedListener::class
        );
    }

    public function test_1_hanya_workflow_a_selesai_project_tetap_operational()
    {
        $this->stepA->status = WorkflowStatus::ENTRY_COMPLETED;
        $this->stepA->save();

        event(new WorkflowACompleted($this->project->id));

        $this->project->refresh();
        $this->assertEquals(ProjectStatus::OPERATIONAL, $this->project->status);
    }

    public function test_2_hanya_workflow_b_selesai_project_tetap_operational()
    {
        $this->stepB->status = WorkflowStatus::AUDIT_REPORT_COMPLETED;
        $this->stepB->save();

        event(new WorkflowBCompleted($this->project->id));

        $this->project->refresh();
        $this->assertEquals(ProjectStatus::OPERATIONAL, $this->project->status);
    }

    public function test_3_keduanya_selesai_berubah_ke_waiting_invoice()
    {
        $this->stepA->status = WorkflowStatus::ENTRY_COMPLETED;
        $this->stepA->save();

        $this->stepB->status = WorkflowStatus::AUDIT_REPORT_COMPLETED;
        $this->stepB->save();

        // Dispatch salah satu (atau dua-duanya)
        event(new WorkflowBCompleted($this->project->id));

        $this->project->refresh();
        $this->assertEquals(ProjectStatus::WAITING_GOVERNMENT_INVOICE, $this->project->status);
    }

    public function test_4_event_selesai_dikirim_dua_kali_hanya_satu_history()
    {
        $this->stepA->status = WorkflowStatus::ENTRY_COMPLETED;
        $this->stepA->save();
        $this->stepB->status = WorkflowStatus::AUDIT_REPORT_COMPLETED;
        $this->stepB->save();

        event(new WorkflowBCompleted($this->project->id));
        event(new WorkflowACompleted($this->project->id)); // Kedua kali

        $logs = \Spatie\Activitylog\Models\Activity::where('subject_id', $this->project->id)
            ->where('event', 'project_status_updated')
            ->where('properties->new_status', ProjectStatus::WAITING_GOVERNMENT_INVOICE->value)
            ->get();

        $this->assertCount(1, $logs);
    }

    public function test_6_reversion_tanpa_alasan_ditolak()
    {
        $this->expectException(\TypeError::class); // Karena argument reason string dan required
        
        $this->project->status = ProjectStatus::WAITING_GOVERNMENT_INVOICE;
        $this->project->save();

        // Pass null or avoid passing argument - PHP akan TypeError jika tidak sesuai kontrak
        $this->reopenService->reopen($this->project->id, 'ENTRY_PROGRESS', WorkflowStatus::ENTRY_NOT_STARTED->value, $this->admin, null);
    }

    public function test_7_user_tidak_berwenang_ditolak()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Anda tidak memiliki kewenangan untuk membuka kembali workflow.");
        
        $this->project->status = ProjectStatus::WAITING_GOVERNMENT_INVOICE;
        $this->project->save();

        $this->reopenService->reopen($this->project->id, 'ENTRY_PROGRESS', WorkflowStatus::ENTRY_NOT_STARTED->value, $this->user, "Revisi");
    }

    public function test_8_reversion_sebelum_invoice_project_kembali_operational()
    {
        $this->stepA->status = WorkflowStatus::ENTRY_COMPLETED;
        $this->stepA->save();
        $this->stepB->status = WorkflowStatus::AUDIT_REPORT_COMPLETED;
        $this->stepB->save();
        $this->project->status = ProjectStatus::WAITING_GOVERNMENT_INVOICE;
        $this->project->save();

        $this->reopenService->reopen($this->project->id, 'ENTRY_PROGRESS', WorkflowStatus::WAITING_CLIENT_DOCUMENTS->value, $this->admin, "Ada dokumen salah");

        $this->project->refresh();
        $this->assertEquals(ProjectStatus::OPERATIONAL, $this->project->status);
    }

    public function test_9_reversion_setelah_invoice_diterbitkan_ditolak()
    {
        $this->project->status = ProjectStatus::WAITING_CERTIFICATE; // Asumsi invoice sudah selesai jika nunggu sertifikat
        $this->project->save();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Reversion tidak diizinkan karena Invoice Negara sudah diterbitkan atau Project sudah melampaui batas.");

        $this->reopenService->reopen($this->project->id, 'ENTRY_PROGRESS', WorkflowStatus::WAITING_CLIENT_DOCUMENTS->value, $this->admin, "Ada dokumen salah");
    }

    public function test_10_update_dropdown_biasa_ditolak_turun_status()
    {
        $this->stepA->status = WorkflowStatus::ENTRY_COMPLETED;
        $this->stepA->save();

        $task = Task::create([
            'project_id' => $this->project->id,
            'assignment_role' => \App\Modules\Projects\Enums\AssignmentRole::ENTRY->value,
            'title' => 'Test Task',
            'task_key' => 'TEST_TASK',
            'task_type' => \App\Modules\Workflows\Enums\TaskType::ENTRY_PROCESS->value,
            'assigned_to' => $this->user->id,
            'status' => \App\Modules\Workflows\Enums\TaskStatus::IN_PROGRESS->value,
            'deadline' => now()->addDays(3),
        ]);

        $service = app(EntryWorkflowService::class);
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Status Entry sudah mencapai batas final, tidak dapat diturunkan secara manual. Gunakan fitur Buka Kembali Workflow.");
        
        $service->updateStatus($task, $this->user, WorkflowStatus::WAITING_CLIENT_DOCUMENTS->value);
    }

    public function test_11_progress_ui_terpisah()
    {
        $this->stepA->status = WorkflowStatus::ENTRY_COMPLETED;
        $this->stepA->save();
        $this->stepB->status = WorkflowStatus::FIELD_AUDIT_COMPLETED;
        $this->stepB->save();

        $progress = WorkflowProgressService::forProject($this->project);

        $this->assertEquals(100, $progress['entry']['percentage']);
        $this->assertEquals(60, $progress['auditor']['percentage']);
    }

    public function test_12_gate_menampilkan_2_dari_2()
    {
        $this->stepA->status = WorkflowStatus::ENTRY_COMPLETED;
        $this->stepA->save();
        $this->stepB->status = WorkflowStatus::AUDIT_REPORT_COMPLETED;
        $this->stepB->save();

        $progress = WorkflowProgressService::forProject($this->project);

        $this->assertEquals(2, $progress['gate']['completed_workflows']);
        $this->assertTrue($progress['gate']['ready']);
    }

    public function test_13_service_invoice_negara_menolak_bypass()
    {
        $this->project->status = ProjectStatus::OPERATIONAL;
        $this->project->save();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Pembuatan Invoice Negara ditolak: Status project saat ini adalah Operasional, bukan Menunggu Invoice Negara.");

        $this->invoiceService->create($this->project->id, $this->admin, []);
    }
}
