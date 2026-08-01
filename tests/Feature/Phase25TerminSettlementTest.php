<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Modules\Clients\Models\Client;
use App\Modules\Clients\Models\Partner;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\ProjectPaymentSchedule;
use App\Modules\Projects\Enums\ProjectStatus;
use App\Modules\Payments\Enums\InvoiceType;
use App\Modules\Payments\Enums\InvoiceAudience;
use App\Modules\Payments\Enums\InvoiceStatus;
use App\Modules\Payments\Enums\PaymentStatus;
use App\Modules\Payments\Models\Invoice;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Services\TerminService;
use App\Modules\Projects\Services\ProjectCompletionService;
use Exception;
use Spatie\Permission\Models\Role;

class Phase25TerminSettlementTest extends TestCase
{
    use RefreshDatabase;

    private User $financeUser;
    private TerminService $terminService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $role = Role::firstOrCreate(['name' => 'Finance']);
        $this->financeUser = User::factory()->create();
        $this->financeUser->assignRole($role);
        
        $this->terminService = app(TerminService::class);
    }

    private function createClientAndProject(bool $isPartner = false): Project
    {
        $partner = $isPartner ? Partner::factory()->create([
            'name' => 'Test Partner', 
            'partner_code' => 'PRT-001', 
        ]) : null;
        
        $client = Client::factory()->create([
            'business_id' => 'PHC-HAL-2023-0001',
            'client_type' => $isPartner ? 'PARTNER' : 'DIRECT',
            'partner_id' => $partner?->id,
        ]);

        $project = Project::factory()->create([
            'client_id' => $client->id,
            'status' => ProjectStatus::CERTIFICATE_ISSUED->value,
            'client_nominal' => 10000000,
            'partner_nominal' => $isPartner ? 5000000 : 0,
            'installment_count' => 2,
        ]);

        return $project;
    }

    public function test_can_issue_next_termin_based_on_schedule()
    {
        $project = $this->createClientAndProject();
        
        // Buat jadwal
        ProjectPaymentSchedule::create([
            'project_id' => $project->id,
            'sequence' => 1,
            'invoice_type' => InvoiceType::INSTALLMENT->value,
            'client_amount' => 3000000,
            'status' => 'PENDING'
        ]);

        $invoices = $this->terminService->issueNextTermin($project, now()->toDateString(), now()->addDays(7)->toDateString(), null, $this->financeUser);
        
        $this->assertCount(1, $invoices);
        $this->assertEquals(InvoiceType::INSTALLMENT, $invoices[0]->invoice_type);
        $this->assertEquals(3000000, $invoices[0]->subtotal);
        
        $this->assertEquals('INVOICED', $project->paymentSchedules()->first()->status);
    }

    public function test_rejects_overinvoicing_on_termin()
    {
        $project = $this->createClientAndProject();
        
        ProjectPaymentSchedule::create([
            'project_id' => $project->id,
            'sequence' => 1,
            'invoice_type' => InvoiceType::INSTALLMENT->value,
            'client_amount' => 11000000, // Melebihi client_nominal 10000000
            'status' => 'PENDING'
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Nominal Termin Client melewati sisa tagihan.");
        
        $this->terminService->issueNextTermin($project, now()->toDateString(), now()->addDays(7)->toDateString(), null, $this->financeUser);
    }

    public function test_partner_client_generates_two_invoices()
    {
        $project = $this->createClientAndProject(true);
        
        ProjectPaymentSchedule::create([
            'project_id' => $project->id,
            'sequence' => 1,
            'invoice_type' => InvoiceType::INSTALLMENT->value,
            'client_amount' => 3000000,
            'partner_amount' => 2000000,
            'status' => 'PENDING'
        ]);

        $invoices = $this->terminService->issueNextTermin($project, now()->toDateString(), now()->addDays(7)->toDateString(), null, $this->financeUser);
        
        $this->assertCount(2, $invoices);
        $this->assertEquals($invoices[0]->billing_group_id, $invoices[1]->billing_group_id);
        
        $this->assertTrue(collect($invoices)->contains('audience', InvoiceAudience::CLIENT));
        $this->assertTrue(collect($invoices)->contains('audience', InvoiceAudience::PARTNER));
    }

    public function test_can_issue_settlement_for_remaining_balance()
    {
        $project = $this->createClientAndProject();
        
        // Buat invoice aktivasi 4 juta yang sudah lunas
        $invoice = Invoice::create([
            'project_id' => $project->id,
            'invoice_number' => 'INV-001',
            'invoice_type' => InvoiceType::ACTIVATION->value,
            'billing_group_id' => 'bg-1',
            'audience' => InvoiceAudience::CLIENT->value,
            'subtotal' => 4000000,
            'status' => InvoiceStatus::PAID->value,
        ]);
        Payment::create([
            'invoice_id' => $invoice->id,
            'payment_number' => 'PAY-001',
            'amount' => 4000000,
            'payment_date' => now(),
            'payment_method' => 'TRANSFER',
            'status' => PaymentStatus::VERIFIED->value
        ]);

        // Sisa tagihan harusnya 6.000.000
        $invoices = $this->terminService->issueSettlement($project, now()->toDateString(), now()->addDays(7)->toDateString(), null, $this->financeUser);
        
        $this->assertCount(1, $invoices);
        $this->assertEquals(InvoiceType::SETTLEMENT, $invoices[0]->invoice_type);
        $this->assertEquals(6000000, $invoices[0]->subtotal);
    }
    
    public function test_project_completion_check_updates_status()
    {
        $project = $this->createClientAndProject();
        
        // Tambah Sertifikat
        \App\Modules\Projects\Models\Certificate::create([
            'project_id' => $project->id,
            'certificate_number' => 'CERT-123',
            'issued_at' => now(),
            'valid_until' => now()->addYears(4),
            'uploaded_by' => $this->financeUser->id,
            'status' => 'ACTIVE'
        ]);
        
        // Buat Invoice Negara LUNAS
        $govInvoice = Invoice::create([
            'project_id' => $project->id,
            'invoice_number' => 'INV-GOV',
            'invoice_type' => InvoiceType::GOVERNMENT->value,
            'billing_group_id' => 'bg-gov',
            'audience' => InvoiceAudience::CLIENT->value,
            'subtotal' => 1000000,
            'status' => InvoiceStatus::PAID->value,
        ]);
        Payment::create([
            'invoice_id' => $govInvoice->id,
            'payment_number' => 'PAY-GOV',
            'amount' => 1000000,
            'payment_date' => now(),
            'payment_method' => 'TRANSFER',
            'status' => PaymentStatus::VERIFIED->value
        ]);
        
        // Buat Pelunasan LUNAS
        $settlement = Invoice::create([
            'project_id' => $project->id,
            'invoice_number' => 'INV-SET',
            'invoice_type' => InvoiceType::SETTLEMENT->value,
            'billing_group_id' => 'bg-set',
            'audience' => InvoiceAudience::CLIENT->value,
            'subtotal' => 10000000,
            'status' => InvoiceStatus::PAID->value,
        ]);
        Payment::create([
            'invoice_id' => $settlement->id,
            'payment_number' => 'PAY-SET',
            'amount' => 10000000,
            'payment_date' => now(),
            'payment_method' => 'TRANSFER',
            'status' => PaymentStatus::VERIFIED->value
        ]);
        
        $this->assertNull($project->completed_at);
        $this->assertEquals(ProjectStatus::CERTIFICATE_ISSUED, $project->status);
        
        app(ProjectCompletionService::class)->checkCompletion($project);
        
        $project->refresh();
        $this->assertEquals(ProjectStatus::COMPLETED, $project->status);
        $this->assertNotNull($project->completed_at);
    }
}
