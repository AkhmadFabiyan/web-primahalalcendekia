<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use App\Filament\Resources\Users\Pages\EditUser;
use Livewire\Livewire;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_super_admin_cannot_deactivate_themselves()
    {
        $superAdmin = User::factory()->create(['status' => 'ACTIVE']);
        $superAdmin->assignRole(Role::SUPER_ADMIN->value);

        $this->actingAs($superAdmin);

        Livewire::test(EditUser::class, [
            'record' => $superAdmin->id,
        ])
        ->fillForm([
            'status' => 'INACTIVE',
            'name' => 'Updated Name',
            'email' => $superAdmin->email,
            'roles' => [DB::table('roles')->where('name', Role::SUPER_ADMIN->value)->first()->id],
        ])
        ->call('save')
        ->assertHasFormErrors(['status']);

        $this->assertEquals('ACTIVE', $superAdmin->fresh()->status);
    }

    public function test_super_admin_cannot_downgrade_last_super_admin()
    {
        $superAdmin1 = User::factory()->create(['status' => 'ACTIVE']);
        $superAdmin1->assignRole(Role::SUPER_ADMIN->value);

        $this->actingAs($superAdmin1);

        Livewire::test(EditUser::class, [
            'record' => $superAdmin1->id,
        ])
        ->fillForm([
            'status' => 'ACTIVE',
            'name' => 'Updated Name',
            'email' => $superAdmin1->email,
            'roles' => [DB::table('roles')->where('name', Role::ADMIN->value)->first()->id],
        ])
        ->call('save')
        ->assertHasFormErrors(['roles']);
    }

    public function test_session_revoked_on_inactive()
    {
        $superAdmin = User::factory()->create(['status' => 'ACTIVE']);
        $superAdmin->assignRole(Role::SUPER_ADMIN->value);

        $staff = User::factory()->create(['status' => 'ACTIVE']);
        $staff->assignRole(Role::ADMIN->value);

        // Dummy session
        DB::table('sessions')->insert([
            'id' => 'dummy_session_123',
            'user_id' => $staff->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test',
            'payload' => 'abc',
            'last_activity' => time(),
        ]);

        $this->assertDatabaseHas('sessions', ['user_id' => $staff->id]);

        $this->actingAs($superAdmin);

        Livewire::test(EditUser::class, [
            'record' => $staff->id,
        ])
        ->fillForm([
            'status' => 'INACTIVE',
            'name' => $staff->name,
            'email' => $staff->email,
            'roles' => [DB::table('roles')->where('name', Role::ADMIN->value)->first()->id],
        ])
        ->call('save')
        ->assertHasNoFormErrors();
        
        $this->assertEquals('INACTIVE', $staff->fresh()->status);

        $this->assertDatabaseMissing('sessions', ['user_id' => $staff->id]);
    }
}
