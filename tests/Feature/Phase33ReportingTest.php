<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Modules\Leads\Models\Lead;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\Certificate;
use App\Modules\Payments\Models\Invoice;
use App\Modules\Payments\Models\Payment;
use App\Modules\Projects\Models\ProjectAssignment;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Carbon\Carbon;
use Filament\Actions\Exports\Enums\ExportFormat;
use App\Filament\Exports\ProjectExporter;

class Phase33ReportingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Because seeder might not create specific permissions yet if phase 33 permission is missing
        Permission::firstOrCreate(['name' => 'report.view']);
        
        $role = Role::firstOrCreate(['name' => 'direktur']);
        $role->givePermissionTo('report.view');
        
        $this->manager = User::factory()->create();
        $this->manager->assignRole('direktur');
        
        $this->staff = User::factory()->create();
    }

    public function test_only_authorized_roles_can_access_report()
    {
        // Let's just verify the page class `canAccess` directly
        $this->actingAs($this->manager);
        $this->assertTrue(\App\Filament\Pages\ManagementReport::canAccess());
        
        $this->actingAs($this->staff);
        $this->assertFalse(\App\Filament\Pages\ManagementReport::canAccess());
    }

    public function test_cohort_lead_conversion_calculation()
    {
        $now = Carbon::now();
        
        // Lead 1: created this month, converted
        $lead1 = Lead::factory()->create(['created_at' => $now]);
        Project::factory()->create(['source_lead_id' => $lead1->id, 'created_at' => $now]);
        
        // Lead 2: created this month, not converted
        $lead2 = Lead::factory()->create(['created_at' => $now]);
        
        // Lead 3: created last month, converted this month (cohort doesn't count if we filter by this month)
        $lead3 = Lead::factory()->create(['created_at' => $now->copy()->subMonth()]);
        Project::factory()->create(['source_lead_id' => $lead3->id, 'created_at' => $now]);
        
        // Lead 4: created this month, DRAFT status (should still count in denominator, not lost)
        $lead4 = Lead::factory()->create(['created_at' => $now, 'status' => 'DRAFT']);
        
        $filterData = \App\Modules\Reports\DataTransferObjects\ManagementReportFilterData::fromArray(['preset' => 'this_month']);
        $service = new \App\Modules\Reports\Services\ManagementReportService($filterData);
        
        $kpis = $service->getKpis();
        
        // Total lead created this month = 3 (lead1, lead2, lead4)
        $this->assertEquals(3, $kpis['total_lead']);
        
        // Project created this month = 2 (lead1, lead3's project), but cohort conversion rate only checks leads created this month
        // Wait, leadDeal in our service is just Projects created in period.
        $this->assertEquals(2, $kpis['lead_deal']);
        
        // Cohort conversion rate: Of leads created this month (3), how many have projects? Just 1 (lead1).
        // Conversion rate = 1/3 = 33.33%
        $this->assertEquals(33.33, $kpis['conversion_rate']);
    }

    public function test_revenue_excludes_government_and_unverified()
    {
        $now = Carbon::now();
        
        $project = Project::factory()->create(['created_at' => $now]);
        
        $bgId = \Illuminate\Support\Str::uuid();
        
        // Commercial Verified
        $inv1 = Invoice::factory()->create(['project_id' => $project->id, 'invoice_type' => 'ACTIVATION', 'billing_group_id' => $bgId, 'audience' => 'CLIENT', 'subtotal' => 1000, 'discount_total' => 0, 'status' => 'PUBLISHED']);
        Payment::factory()->create(['invoice_id' => $inv1->id, 'amount' => 1000, 'status' => 'VERIFIED', 'payment_date' => $now, 'payment_number' => 'PAY-1', 'payment_method' => 'BANK_TRANSFER']);
        
        // Commercial Unverified
        $inv2 = Invoice::factory()->create(['project_id' => $project->id, 'invoice_type' => 'INSTALLMENT', 'billing_group_id' => $bgId, 'audience' => 'CLIENT', 'subtotal' => 2000, 'discount_total' => 0, 'status' => 'PUBLISHED']);
        Payment::factory()->create(['invoice_id' => $inv2->id, 'amount' => 2000, 'status' => 'PENDING', 'payment_date' => $now, 'payment_number' => 'PAY-2', 'payment_method' => 'BANK_TRANSFER']);
        
        // Government Verified
        $inv3 = Invoice::factory()->create(['project_id' => $project->id, 'invoice_type' => 'GOVERNMENT', 'billing_group_id' => $bgId, 'audience' => 'CLIENT', 'subtotal' => 5000, 'discount_total' => 0, 'status' => 'PUBLISHED']);
        Payment::factory()->create(['invoice_id' => $inv3->id, 'amount' => 5000, 'status' => 'VERIFIED', 'payment_date' => $now, 'payment_number' => 'PAY-3', 'payment_method' => 'BANK_TRANSFER']);
        
        $filterData = \App\Modules\Reports\DataTransferObjects\ManagementReportFilterData::fromArray(['preset' => 'this_month']);
        $service = new \App\Modules\Reports\Services\ManagementReportService($filterData);
        
        $kpis = $service->getKpis();
        
        $this->assertEquals(1000, $kpis['kas_masuk']); // Only inv1
    }

    public function test_cycle_time_calculation()
    {
        $now = Carbon::now();
        
        $project1 = Project::factory()->create(['activated_at' => $now->copy()->subDays(10)]);
        Certificate::create(['project_id' => $project1->id, 'issued_at' => $now, 'certificate_number' => 'CERT-1', 'uploaded_by' => $this->manager->id]);
        
        $project2 = Project::factory()->create(['activated_at' => $now->copy()->subDays(20)]);
        Certificate::create(['project_id' => $project2->id, 'issued_at' => $now, 'certificate_number' => 'CERT-2', 'uploaded_by' => $this->manager->id]);
        
        $project3 = Project::factory()->create(['activated_at' => $now->copy()->subDays(30)]); // no certificate
        
        $filterData = \App\Modules\Reports\DataTransferObjects\ManagementReportFilterData::fromArray(['preset' => 'this_month']);
        $service = new \App\Modules\Reports\Services\ManagementReportService($filterData);
        
        $metrics = $service->getCycleTimeMetrics();
        
        // Let's use assertGreaterThanOrEqual and assertLessThanOrEqual to allow small time shifts
        $this->assertGreaterThanOrEqual(9, $metrics['min']);
        $this->assertLessThanOrEqual(10, $metrics['min']);
        $this->assertGreaterThanOrEqual(19, $metrics['max']);
        $this->assertLessThanOrEqual(20, $metrics['max']);
        $this->assertGreaterThanOrEqual(14, $metrics['avg']);
        $this->assertLessThanOrEqual(15, $metrics['avg']);
    }

    public function test_csv_injection_sanitizer()
    {
        // Access protected method for testing
        $reflection = new \ReflectionClass(ProjectExporter::class);
        $method = $reflection->getMethod('sanitizeCsvFormula');
        
        $this->assertEquals("'=SUM(A1:A10)", $method->invoke(null, '=SUM(A1:A10)'));
        $this->assertEquals("'+123", $method->invoke(null, '+123'));
        $this->assertEquals("'-123", $method->invoke(null, '-123'));
        $this->assertEquals("'@cmd", $method->invoke(null, '@cmd'));
        $this->assertEquals("Normal Text", $method->invoke(null, 'Normal Text'));
    }
}
