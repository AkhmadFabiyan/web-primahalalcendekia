<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_user_can_login_and_regenerate_session()
    {
        $user = User::factory()->create([
            'status' => 'ACTIVE',
            'password' => bcrypt('password123'),
        ]);

        $this->get('/dashboard/login')->assertSuccessful();

        $oldSessionId = session()->getId();

        Livewire::test(Login::class)
            ->fillForm([
                'email' => $user->email,
                'password' => 'password123',
                'remember' => false,
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors()
            ->assertRedirect(route('filament.admin.pages.dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotEquals($oldSessionId, session()->getId());

        // Assert exactly one activity log
        $this->assertDatabaseHas('activity_log', [
            'description' => 'Login berhasil',
            'causer_id' => $user->id,
        ]);
    }

    public function test_inactive_user_is_rejected_without_403()
    {
        $user = User::factory()->create([
            'status' => 'INACTIVE',
            'password' => bcrypt('password123'),
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'email' => $user->email,
                'password' => 'password123',
                'remember' => false,
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);

        $this->assertGuest();
    }

    public function test_soft_deleted_user_is_rejected()
    {
        $user = User::factory()->create([
            'status' => 'ACTIVE',
            'password' => bcrypt('password123'),
            'deleted_at' => now(),
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'email' => $user->email,
                'password' => 'password123',
                'remember' => false,
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);

        $this->assertGuest();
    }

    public function test_changing_status_to_inactive_revokes_sessions()
    {
        $user = User::factory()->create([
            'status' => 'ACTIVE',
            'password' => bcrypt('password123'),
        ]);

        // Mock a session in DB
        DB::table('sessions')->insert([
            'id' => 'fake_session_id',
            'user_id' => $user->id,
            'payload' => 'fake_payload',
            'last_activity' => time(),
        ]);

        $this->assertDatabaseHas('sessions', ['user_id' => $user->id]);

        $user->status = 'INACTIVE';
        $user->save();

        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
    }

    public function test_logout_invalidates_session()
    {
        $user = User::factory()->create([
            'status' => 'ACTIVE',
        ]);

        $this->actingAs($user);
        $oldSessionId = session()->getId();

        $this->post('/dashboard/logout')->assertRedirect('/dashboard/login');

        $this->assertGuest();
        $this->assertNotEquals($oldSessionId, session()->getId());

        $this->assertDatabaseHas('activity_log', [
            'description' => 'Logout berhasil',
            'causer_id' => $user->id,
        ]);
    }
}
