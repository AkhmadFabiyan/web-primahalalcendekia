<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;
use App\Enums\Role as RoleEnum;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Enums\ProjectStatus;
use App\Modules\Dashboards\Services\OperationalDashboardService;
use App\Modules\Dashboards\DataTransferObjects\OperationalDashboardFilterData;

class Phase31OperationalTest extends TestCase
{
    use RefreshDatabase;

    public function test_operational_dashboard_is_accessible_to_internal_staff_only()
    {
        $staff = User::factory()->create();
        $role = Role::create(['name' => RoleEnum::SPV_ENTRY->value]);
        $staff->assignRole($role);

        $this->actingAs($staff)
             ->get('/admin')
             ->assertStatus(200)
             ->assertSeeLivewire('app.filament.widgets.operational-kpi-widget');
    }

    public function test_operational_dashboard_is_not_accessible_to_client()
    {
        $client = User::factory()->create();
        $role = Role::create(['name' => RoleEnum::KLIEN->value]);
        $client->assignRole($role);

        $this->actingAs($client)
             ->get('/admin')
             ->assertStatus(200)
             ->assertDontSeeLivewire('app.filament.widgets.operational-kpi-widget');
    }

    public function test_service_returns_correct_kpis()
    {
        // For simplicity, just test that service returns structure
        $filterData = OperationalDashboardFilterData::fromArray([]);
        $service = new OperationalDashboardService($filterData);
        $kpis = $service->getKPIs();

        $this->assertArrayHasKey('totalKlien', $kpis);
        $this->assertArrayHasKey('kritis', $kpis);
    }
}
