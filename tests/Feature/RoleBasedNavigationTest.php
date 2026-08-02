<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Resources\DocumentAdministration\DocumentAdministrationResource;
use App\Filament\Support\RoleNavigation;
use App\Models\User;
use App\Modules\Projects\Enums\AssignmentRole;
use App\Modules\Projects\Enums\ProjectStatus;
use App\Modules\Projects\Models\Project;
use App\Modules\Workflows\Enums\TaskPriority;
use App\Modules\Workflows\Enums\TaskStatus;
use App\Modules\Workflows\Enums\TaskType;
use App\Modules\Workflows\Models\Task;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleBasedNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_workflow_navigation_category_follows_each_role_stage(): void
    {
        $expectations = [
            Role::MARKETING->value => RoleNavigation::ACQUISITION,
            Role::FINANCE->value => RoleNavigation::FINANCE,
            Role::ADMIN->value => RoleNavigation::DOCUMENTS,
            Role::ENTRY->value => RoleNavigation::ENTRY,
            Role::SPV_ENTRY->value => RoleNavigation::ENTRY_REVIEW,
            Role::PENDAMPING_AUDITOR->value => RoleNavigation::AUDIT_ASSISTANCE,
            Role::AUDITOR->value => RoleNavigation::AUDIT_REVIEW,
            Role::ADMIN_PERUSAHAAN->value => RoleNavigation::FINALIZATION,
            Role::DIREKTUR->value => RoleNavigation::MONITORING,
            Role::MANAGER_OPERASIONAL->value => RoleNavigation::MONITORING,
        ];

        foreach ($expectations as $role => $expectedGroup) {
            $user = User::factory()->create();
            $user->syncRoles([$role]);

            $this->assertSame($expectedGroup, RoleNavigation::forModule('tasks', $user));
            $this->assertSame($expectedGroup, RoleNavigation::forModule('clients', $user));
        }
    }

    public function test_admin_document_queue_only_contains_their_assigned_projects(): void
    {
        $admin = User::factory()->create();
        $admin->syncRoles([Role::ADMIN->value]);

        $otherAdmin = User::factory()->create();
        $otherAdmin->syncRoles([Role::ADMIN->value]);

        $ownTask = $this->createDocumentTask($admin);
        $otherTask = $this->createDocumentTask($otherAdmin);

        $this->actingAs($admin);

        $taskIds = DocumentAdministrationResource::getEloquentQuery()->pluck('id');

        $this->assertTrue($taskIds->contains($ownTask->id));
        $this->assertFalse($taskIds->contains($otherTask->id));
    }

    public function test_admin_can_render_document_queue_table(): void
    {
        $admin = User::factory()->create();
        $admin->syncRoles([Role::ADMIN->value]);
        $task = $this->createDocumentTask($admin);

        $this->actingAs($admin)
            ->get(DocumentAdministrationResource::getUrl('index'))
            ->assertOk()
            ->assertSee($task->project->client->business_id)
            ->assertSee('Dokumen Wajib Terpenuhi');
    }

    private function createDocumentTask(User $admin): Task
    {
        $project = Project::factory()->create([
            'status' => ProjectStatus::ACTIVE,
            'activated_at' => now(),
        ]);

        return Task::create([
            'project_id' => $project->id,
            'assigned_to' => $admin->id,
            'assignment_role' => AssignmentRole::ADMIN,
            'task_type' => TaskType::DOCUMENT_COMPLETION,
            'task_key' => "PROJECT-{$project->id}:INITIAL_DOCUMENT_COMPLETION",
            'title' => 'Lengkapi Dokumen Persyaratan Klien',
            'priority' => TaskPriority::MEDIUM,
            'status' => TaskStatus::TODO,
            'entered_at' => now(),
            'deadline' => now()->addDays(3),
        ]);
    }
}
