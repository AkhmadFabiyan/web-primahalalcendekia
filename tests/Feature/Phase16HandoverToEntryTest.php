<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Clients\Models\Client;
use App\Modules\Projects\Enums\AssignmentRole;
use App\Modules\Projects\Enums\ProjectStatus;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\SihalalCredential;
use App\Modules\Projects\Services\ProjectHandoverService;
use App\Modules\Workflows\Enums\WorkflowLane;
use App\Modules\Workflows\Enums\WorkflowStatus;
use App\Modules\Workflows\Models\Task;
use App\Modules\Workflows\Models\WorkflowStep;
use App\Modules\Documents\Services\DocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class Phase16HandoverToEntryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Setup base roles & permissions
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $entryRole = Role::firstOrCreate(['name' => 'Entry']);
        Permission::firstOrCreate(['name' => 'sihalal_credentials.manage'])->assignRole($adminRole);
        Permission::firstOrCreate(['name' => 'sihalal_credentials.reveal'])->assignRole($entryRole);

        $documentServiceMock = Mockery::mock(DocumentService::class);
        $documentServiceMock->shouldReceive('checkCompleteness')->andReturnNull();
        $this->app->instance(DocumentService::class, $documentServiceMock);
    }

    public function test_credentials_are_encrypted_in_database()
    {
        $project = Project::factory()->create();
        
        $credential = SihalalCredential::create([
            'project_id' => $project->id,
            'email_encrypted' => 'test@example.com',
            'password_encrypted' => 'secret123',
        ]);

        // Assert raw value in DB is different from plain text
        $raw = DB::table('sihalal_credentials')->where('id', $credential->id)->first();
        $this->assertNotEquals('test@example.com', $raw->email_encrypted);
        $this->assertNotEquals('secret123', $raw->password_encrypted);

        // Assert model decrypts it correctly
        $credential->refresh();
        $this->assertEquals('test@example.com', $credential->email_encrypted);
        $this->assertEquals('secret123', $credential->password_encrypted);

        // Assert hidden from JSON
        $array = $credential->toArray();
        $this->assertArrayNotHasKey('email_encrypted', $array);
        $this->assertArrayNotHasKey('password_encrypted', $array);
    }

    public function test_handover_fails_if_project_not_active()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        
        $project = Project::factory()->create(['status' => ProjectStatus::WAITING_ACTIVATION]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Handoff ditolak: Project tidak aktif.');

        app(ProjectHandoverService::class)->handoverToEntry($project, $admin);
    }

    public function test_handover_fails_if_documents_not_complete()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        
        $project = Project::factory()->create(['status' => ProjectStatus::ACTIVE]);
        
        WorkflowStep::create([
            'project_id' => $project->id,
            'step_code' => 'DOCUMENT_ADMINISTRATION',
            'workflow_lane' => WorkflowLane::A->value,
            'status' => WorkflowStatus::IN_PROGRESS->value,
            'is_required' => true,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Handoff ditolak: Dokumen belum lengkap atau terdapat revisi yang masih terbuka.');

        app(ProjectHandoverService::class)->handoverToEntry($project, $admin);
    }

    public function test_handover_fails_if_credential_missing()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        
        $project = Project::factory()->create(['status' => ProjectStatus::ACTIVE]);
        
        WorkflowStep::create([
            'project_id' => $project->id,
            'step_code' => 'DOCUMENT_ADMINISTRATION',
            'workflow_lane' => WorkflowLane::A->value,
            'status' => WorkflowStatus::COMPLETE->value,
            'is_required' => true,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Handoff ditolak: Kredensial SIHALAL belum tersedia.');

        app(ProjectHandoverService::class)->handoverToEntry($project, $admin);
    }

    public function test_handover_fails_if_pic_entry_not_assigned()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        
        $project = Project::factory()->create(['status' => ProjectStatus::ACTIVE]);
        
        WorkflowStep::create([
            'project_id' => $project->id,
            'step_code' => 'DOCUMENT_ADMINISTRATION',
            'workflow_lane' => WorkflowLane::A->value,
            'status' => WorkflowStatus::COMPLETE->value,
            'is_required' => true,
        ]);

        SihalalCredential::create([
            'project_id' => $project->id,
            'email_encrypted' => 'a@b.com',
            'password_encrypted' => 'pass',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Handoff ditolak: PIC Entry belum ditentukan atau tidak aktif.');

        app(ProjectHandoverService::class)->handoverToEntry($project, $admin);
    }

    public function test_handover_success_creates_task_and_updates_steps()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        
        $entryPic = User::factory()->create();
        $entryPic->assignRole('Entry');

        $project = Project::factory()->create(['status' => ProjectStatus::ACTIVE]);
        
        $project->assignments()->create([
            'user_id' => $entryPic->id,
            'assignment_role' => AssignmentRole::ENTRY->value,
        ]);
        
        WorkflowStep::create([
            'project_id' => $project->id,
            'step_code' => 'DOCUMENT_ADMINISTRATION',
            'workflow_lane' => WorkflowLane::A->value,
            'status' => WorkflowStatus::COMPLETE->value,
            'is_required' => true,
        ]);
        
        WorkflowStep::create([
            'project_id' => $project->id,
            'step_code' => 'ENTRY_PROGRESS',
            'workflow_lane' => WorkflowLane::A->value,
            'status' => 'ENTRY_NOT_STARTED',
            'is_required' => true,
        ]);

        SihalalCredential::create([
            'project_id' => $project->id,
            'email_encrypted' => 'a@b.com',
            'password_encrypted' => 'pass',
        ]);

        app(ProjectHandoverService::class)->handoverToEntry($project, $admin);

        // Assert ENTRY_READINESS created
        $this->assertDatabaseHas('workflow_steps', [
            'project_id' => $project->id,
            'step_code' => 'ENTRY_READINESS',
            'status' => WorkflowStatus::COMPLETE->value,
        ]);

        // Assert ENTRY_PROGRESS started_at updated but status unchanged
        $entryProgress = WorkflowStep::where('project_id', $project->id)->where('step_code', 'ENTRY_PROGRESS')->first();
        $this->assertNotNull($entryProgress->started_at);
        $this->assertEquals(WorkflowStatus::ENTRY_NOT_STARTED, $entryProgress->status);

        // Assert Task Created
        $this->assertDatabaseHas('tasks', [
            'project_id' => $project->id,
            'assigned_to' => $entryPic->id,
            'task_type' => 'ENTRY_PROCESS',
        ]);
    }
}
