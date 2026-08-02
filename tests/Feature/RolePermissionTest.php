<?php

namespace Tests\Feature;

use App\Enums\Permission as PermissionEnum;
use App\Enums\Role as RoleEnum;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_seeder_creates_all_roles_with_guard_web()
    {
        $rolesCount = count(RoleEnum::cases());
        $this->assertDatabaseCount('roles', $rolesCount);

        foreach (RoleEnum::cases() as $roleEnum) {
            $this->assertDatabaseHas('roles', [
                'name' => $roleEnum->value,
                'guard_name' => 'web',
            ]);
        }
    }

    public function test_seeder_creates_all_permissions_without_delete_operational()
    {
        $permissionsCount = count(PermissionEnum::cases());
        $this->assertDatabaseCount('permissions', $permissionsCount);

        // Assert no 'delete' or 'create_clients'
        $this->assertDatabaseMissing('permissions', ['name' => 'clients.create']);
        $this->assertDatabaseMissing('permissions', ['name' => 'projects.create']);
        $this->assertDatabaseMissing('permissions', ['name' => 'leads.delete']);
    }

    public function test_seeder_is_idempotent()
    {
        // Run again, should not throw constraint violations or duplicate entries
        $this->seed(RolePermissionSeeder::class);

        $rolesCount = count(RoleEnum::cases());
        $this->assertDatabaseCount('roles', $rolesCount);

        $permissionsCount = count(PermissionEnum::cases());
        $this->assertDatabaseCount('permissions', $permissionsCount);
    }

    public function test_user_cannot_have_more_than_one_role()
    {
        $user = User::factory()->create();

        $user->syncRoles([RoleEnum::MARKETING->value]);
        $this->assertTrue($user->hasRole(RoleEnum::MARKETING->value));
        $this->assertEquals(1, $user->roles()->count());

        // Assign another role using syncRoles
        $user->syncRoles([RoleEnum::FINANCE->value]);
        $this->assertTrue($user->hasRole(RoleEnum::FINANCE->value));
        $this->assertFalse($user->hasRole(RoleEnum::MARKETING->value));
        $this->assertEquals(1, $user->roles()->count());
    }

    public function test_only_marketing_and_super_admin_have_leads_create()
    {
        $marketing = Role::findByName(RoleEnum::MARKETING->value);
        $this->assertTrue($marketing->hasPermissionTo(PermissionEnum::CreateLeads->value));

        $superAdmin = Role::findByName(RoleEnum::SUPER_ADMIN->value);
        $this->assertTrue($superAdmin->hasPermissionTo(PermissionEnum::CreateLeads->value));

        $finance = Role::findByName(RoleEnum::FINANCE->value);
        $this->assertFalse($finance->hasPermissionTo(PermissionEnum::CreateLeads->value));
    }

    public function test_marketing_can_view_clients_but_not_update()
    {
        $marketing = Role::findByName(RoleEnum::MARKETING->value);

        $this->assertTrue($marketing->hasPermissionTo(PermissionEnum::ViewClients->value));
        $this->assertFalse($marketing->hasPermissionTo(PermissionEnum::UpdateClients->value));
    }

    public function test_klien_has_only_dashboard_view_permission()
    {
        $klien = Role::findByName(RoleEnum::KLIEN->value);

        $this->assertTrue($klien->hasPermissionTo(PermissionEnum::ViewDashboard->value));
        $this->assertFalse($klien->hasPermissionTo(PermissionEnum::ViewClients->value));
        $this->assertFalse($klien->hasPermissionTo(PermissionEnum::ViewTasks->value));
        $this->assertFalse($klien->hasPermissionTo(PermissionEnum::ViewInvoices->value));
        $this->assertTrue($klien->hasPermissionTo(PermissionEnum::ViewArchives->value));
        $this->assertTrue($klien->hasPermissionTo(PermissionEnum::DownloadClientArchives->value));
        $this->assertEquals(3, $klien->permissions()->count());
    }

    public function test_super_admin_has_all_registered_permissions()
    {
        $superAdmin = Role::findByName(RoleEnum::SUPER_ADMIN->value);
        $permissionsCount = count(PermissionEnum::cases());

        $this->assertEquals($permissionsCount, $superAdmin->permissions()->count());
        $this->assertTrue($superAdmin->hasPermissionTo(PermissionEnum::UpdatePostAudit->value));
    }

    public function test_helpers_work_correctly()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->syncRoles([RoleEnum::SUPER_ADMIN->value]);
        $this->assertTrue($superAdmin->isSuperAdmin());
        $this->assertFalse($superAdmin->isClient());
        $this->assertTrue($superAdmin->isInternalStaff());

        $client = User::factory()->create();
        $client->syncRoles([RoleEnum::KLIEN->value]);
        $this->assertFalse($client->isSuperAdmin());
        $this->assertTrue($client->isClient());
        $this->assertFalse($client->isInternalStaff());
    }

    public function test_403_redirects_to_dashboard_for_web_and_returns_404_for_api()
    {
        // Define dummy routes to trigger 403
        Route::get('/test-403', function () {
            throw new AccessDeniedHttpException;
        })->middleware('web');

        Route::get('/api/test-403', function () {
            throw new AuthorizationException;
        });

        // Test Web request
        $response = $this->get('/test-403');
        $response->assertRedirect('/dashboard');

        // Test API request
        $apiResponse = $this->getJson('/api/test-403');
        $apiResponse->assertStatus(404);
        $apiResponse->assertJson(['message' => 'Not Found.']);
    }
}
