<?php

namespace Tests\Feature;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class Phase30DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Roles and Permissions
        Role::firstOrCreate(['name' => \App\Enums\Role::SUPER_ADMIN->value]);
        Role::firstOrCreate(['name' => \App\Enums\Role::KLIEN->value]);
        Role::firstOrCreate(['name' => \App\Enums\Role::ENTRY->value]);
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'clients.view']);
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'projects.view']);
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'tasks.view']);
    }

    public function test_super_admin_can_see_system_dashboard_widgets()
    {
        $superAdmin = User::factory()->create(['status' => 'ACTIVE']);
        $superAdmin->assignRole(\App\Enums\Role::SUPER_ADMIN->value);

        $this->actingAs($superAdmin)
            ->get('/admin')
            ->assertStatus(200)
            ->assertSeeLivewire(\App\Filament\Widgets\SystemHealthWidget::class)
            ->assertDontSeeLivewire(\App\Filament\Widgets\PersonalWorkloadWidget::class)
            ->assertDontSeeLivewire(\App\Filament\Widgets\ClientOverviewWidget::class);
    }

    public function test_internal_staff_can_see_personal_dashboard_widgets()
    {
        $staff = User::factory()->create(['status' => 'ACTIVE']);
        $staff->assignRole(\App\Enums\Role::ENTRY->value);

        $this->actingAs($staff)
            ->get('/admin')
            ->assertStatus(200)
            ->assertSeeLivewire(\App\Filament\Widgets\PersonalWorkloadWidget::class)
            ->assertSeeLivewire(\App\Filament\Widgets\MyTasksWidget::class)
            ->assertDontSeeLivewire(\App\Filament\Widgets\SystemHealthWidget::class);
    }

    public function test_client_can_see_client_dashboard_widgets_and_redirects_from_internal()
    {
        $clientModel = \App\Modules\Clients\Models\Client::factory()->create();
        $client = User::factory()->create(['status' => 'ACTIVE', 'client_id' => $clientModel->id]);
        $client->assignRole(\App\Enums\Role::KLIEN->value);

        // Dashboard uses param ?section=overview by default
        $this->actingAs($client)
            ->get('/admin?section=overview')
            ->assertStatus(200)
            ->assertSeeLivewire(\App\Filament\Widgets\ClientOverviewWidget::class)
            ->assertDontSeeLivewire(\App\Filament\Widgets\PersonalWorkloadWidget::class);

        // Accessing internal pages should redirect to dashboard
        $this->actingAs($client)
            ->get('/admin/users')
            ->assertRedirect('/admin');
    }

    public function test_client_without_client_id_handles_safely()
    {
        $client = User::factory()->create(['status' => 'ACTIVE', 'client_id' => null]);
        $client->assignRole(\App\Enums\Role::KLIEN->value);

        $this->actingAs($client)
            ->get('/admin?section=overview')
            ->assertStatus(200)
            ->assertSeeLivewire(\App\Filament\Widgets\ClientOverviewWidget::class)
            ->assertSee('Belum ada Project'); // Empty state text
    }

    public function test_invalid_section_resets_to_overview_for_client()
    {
        $clientModel = \App\Modules\Clients\Models\Client::factory()->create();
        $client = User::factory()->create(['status' => 'ACTIVE', 'client_id' => $clientModel->id]);
        $client->assignRole(\App\Enums\Role::KLIEN->value);

        Livewire::actingAs($client)
            ->test(\App\Filament\Pages\Dashboard::class, ['section' => 'invalid_section'])
            ->assertSet('section', 'overview');
    }
}
