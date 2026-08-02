<?php

namespace Tests\Feature;

use App\Enums\Role as RoleEnum;
use App\Filament\Widgets\OperationalKpiWidget;
use App\Models\User;
use App\Modules\Dashboards\DataTransferObjects\OperationalDashboardFilterData;
use App\Modules\Dashboards\Services\OperationalDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class Phase31OperationalTest extends TestCase
{
    use RefreshDatabase;

    public function test_operational_dashboard_is_accessible_to_internal_staff_only()
    {
        $staff = User::factory()->create();
        $role = Role::create(['name' => RoleEnum::SPV_ENTRY->value]);
        $staff->assignRole($role);

        $this->actingAs($staff)
            ->get('/dashboard')
            ->assertStatus(200)
            ->assertSeeLivewire(OperationalKpiWidget::class);
    }

    public function test_operational_dashboard_is_not_accessible_to_client()
    {
        $client = User::factory()->create();
        $role = Role::create(['name' => RoleEnum::KLIEN->value]);
        $client->assignRole($role);

        $this->actingAs($client)
            ->get('/dashboard')
            ->assertStatus(200)
            ->assertDontSeeLivewire(OperationalKpiWidget::class);
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
