<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Clients\Models\Client;
use App\Modules\Payments\Enums\InvoiceStatus;
use App\Modules\Payments\Enums\InvoiceType;
use App\Modules\Payments\Events\ActivationBillingGroupPaid;
use App\Modules\Payments\Models\Invoice;
use App\Modules\Projects\Enums\AssignmentRole;
use App\Modules\Projects\Enums\ProjectStatus;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\ProjectAssignment;
use App\Modules\Workflows\Enums\TaskStatus;
use App\Modules\Workflows\Models\Task;
use App\Modules\Workflows\Models\TaskAssignmentHistory;
use App\Modules\Workflows\Services\TaskService;
use App\Modules\Projects\Services\AssignmentService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class Phase14TaskAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Buat roles
        $roles = [
            'Super Admin', 'Manager Operasional', 'Admin', 'Admin Perusahaan', 
            'Entry', 'SPV Entry', 'Pendamping Auditor', 'Auditor'
        ];
        
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }
    }

    public function test_project_aktif_tanpa_admin_belum_membuat_task()
    {
        \Illuminate\Support\Facades\Notification::fake();

        $project = Project::factory()->create([
            'status' => ProjectStatus::WAITING_ACTIVATION
        ]);

        $invoice = Invoice::factory()->create([
            'project_id' => $project->id,
            'billing_group_id' => 'GRP-001',
            'audience' => \App\Modules\Payments\Enums\InvoiceAudience::CLIENT->value,
            'invoice_type' => InvoiceType::ACTIVATION,
            'status' => InvoiceStatus::PAID,
            'subtotal' => 100,
            'discount_total' => 0
        ]);

        $admin = User::factory()->create();

        event(new ActivationBillingGroupPaid(
            $project->id,
            $invoice->billing_group_id,
            'PAY-001',
            $admin->id
        ));

        $project->refresh();
        $this->assertEquals(ProjectStatus::ACTIVE, $project->status);
        $this->assertEquals(0, Task::where('project_id', $project->id)->count());
    }

    public function test_assignment_admin_pada_project_aktif_membuat_satu_task_awal()
    {
        $project = Project::factory()->create([
            'status' => ProjectStatus::ACTIVE
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $manager = User::factory()->create();
        $manager->assignRole('Manager Operasional');
        $this->actingAs($manager);

        $assignmentService = $this->app->make(AssignmentService::class);
        $assignmentService->reassign($project, AssignmentRole::ADMIN, $admin);

        $task = Task::where('project_id', $project->id)->first();
        $this->assertNotNull($task);
        $this->assertEquals('Lengkapi Dokumen Persyaratan Klien', $task->title);
        $this->assertEquals($admin->id, $task->assigned_to);
    }

    public function test_project_dengan_admin_sudah_ditentukan_membuat_task_saat_aktivasi()
    {
        \Illuminate\Support\Facades\Notification::fake();

        $project = Project::factory()->create([
            'status' => ProjectStatus::WAITING_ACTIVATION
        ]);
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        ProjectAssignment::create([
            'project_id' => $project->id,
            'user_id' => $admin->id,
            'assignment_role' => AssignmentRole::ADMIN->value,
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
        ]);

        $invoice = Invoice::factory()->create([
            'project_id' => $project->id,
            'billing_group_id' => 'GRP-002',
            'audience' => \App\Modules\Payments\Enums\InvoiceAudience::CLIENT->value,
            'invoice_type' => InvoiceType::ACTIVATION,
            'status' => InvoiceStatus::PAID,
            'subtotal' => 100,
            'discount_total' => 0
        ]);

        event(new ActivationBillingGroupPaid(
            $project->id,
            $invoice->billing_group_id,
            'PAY-001',
            $admin->id
        ));

        $task = Task::where('project_id', $project->id)->first();
        $this->assertNotNull($task);
        $this->assertEquals($admin->id, $task->assigned_to);
    }

    public function test_reassignment_menutup_record_lama_dan_membuat_baru()
    {
        $project = Project::factory()->create([
            'status' => ProjectStatus::ACTIVE
        ]);
        
        $admin1 = User::factory()->create();
        $admin2 = User::factory()->create();

        $manager = User::factory()->create();
        $manager->assignRole('Manager Operasional');
        $this->actingAs($manager);

        $service = $this->app->make(AssignmentService::class);
        
        // assign admin 1
        $service->reassign($project, AssignmentRole::ADMIN, $admin1);
        
        $this->assertDatabaseHas('project_assignments', [
            'project_id' => $project->id,
            'user_id' => $admin1->id,
            'ended_at' => null
        ]);

        // assign admin 2 with reason
        $service->reassign($project, AssignmentRole::ADMIN, $admin2, 'Cuti');

        $this->assertDatabaseHas('project_assignments', [
            'project_id' => $project->id,
            'user_id' => $admin1->id,
        ]);
        $this->assertDatabaseMissing('project_assignments', [
            'project_id' => $project->id,
            'user_id' => $admin1->id,
            'ended_at' => null
        ]);

        $this->assertDatabaseHas('project_assignments', [
            'project_id' => $project->id,
            'user_id' => $admin2->id,
            'ended_at' => null
        ]);

        // Cek task dipindah
        $task = Task::where('project_id', $project->id)->first();
        $this->assertEquals($admin2->id, $task->assigned_to);

        // Cek histori
        $this->assertDatabaseHas('task_assignment_histories', [
            'task_id' => $task->id,
            'from_user_id' => $admin1->id,
            'to_user_id' => $admin2->id,
            'ended_at' => null,
            'reason' => 'Cuti'
        ]);

        $this->assertDatabaseMissing('task_assignment_histories', [
            'task_id' => $task->id,
            'from_user_id' => null,
            'to_user_id' => $admin1->id,
            'ended_at' => null
        ]);
    }
}
