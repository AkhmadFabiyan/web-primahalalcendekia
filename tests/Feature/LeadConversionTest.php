<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Modules\Leads\Models\Lead;
use App\Modules\Clients\Models\Client;
use App\Modules\Clients\Models\Partner;
use App\Modules\Projects\Models\Project;
use App\Modules\Payments\Models\Invoice;
use App\Modules\Leads\Services\LeadConversionService;
use App\Enums\Role;
use App\Modules\Leads\Enums\LeadStatus;
use App\Modules\Clients\Enums\ClientType;
use App\Modules\Leads\Enums\PaymentScheme;
use App\Modules\Payments\Enums\InvoiceAudience;
use Spatie\Permission\Models\Role as SpatieRole;

class LeadConversionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed basic roles
        SpatieRole::firstOrCreate(['name' => Role::MARKETING->value]);
        SpatieRole::firstOrCreate(['name' => Role::SUPER_ADMIN->value]);
    }

    private function createMarketingUser()
    {
        $user = User::factory()->create(['status' => 'ACTIVE']);
        $user->assignRole(Role::MARKETING->value);
        return $user;
    }

    private function createLead($type = ClientType::DIRECT)
    {
        return Lead::factory()->create([
            'client_type' => $type,
            'marketing_id' => $this->createMarketingUser()->id,
            'status' => LeadStatus::DRAFT,
            'payment_scheme' => PaymentScheme::INSTALLMENT,
            'client_nominal' => 1000000,
            'partner_nominal' => $type === ClientType::PARTNER ? 800000 : null,
            'installment_count' => 2,
            'partner_name' => $type === ClientType::PARTNER ? 'Test Partner Baru' : null,
            'pic_email' => 'test@example.com',
            'partner_email' => $type === ClientType::PARTNER ? 'partner@example.com' : null,
            'partner_pic_name' => $type === ClientType::PARTNER ? 'PIC Partner' : null,
            'partner_phone' => $type === ClientType::PARTNER ? '08123456789' : null,
            'service_type' => 'Sertifikasi Halal',
        ]);
    }

    public function test_conversion_direct_creates_one_invoice()
    {
        $lead = $this->createLead(ClientType::DIRECT);
        $service = new LeadConversionService();
        $project = $service->convert($lead);

        $this->assertNotNull($project);
        $this->assertEquals($lead->id, $project->source_lead_id);
        $this->assertEquals($lead->company_name, $project->client->company_name);

        $invoices = Invoice::where('project_id', $project->id)->get();
        $this->assertCount(1, $invoices);
        $this->assertEquals(InvoiceAudience::CLIENT, $invoices[0]->audience);
        $this->assertNull($invoices[0]->invoice_number);
        $this->assertEquals(500000, $invoices[0]->subtotal); // 1,000,000 / 2
        
        // Check assignments
        $this->assertEquals(1, $project->assignments()->count());
        $this->assertEquals($lead->marketing_id, $project->assignments()->first()->user_id);
        
        $lead->refresh();
        $this->assertEquals(LeadStatus::DEAL, $lead->status);
    }

    public function test_conversion_partner_creates_two_invoices()
    {
        $lead = $this->createLead(ClientType::PARTNER);
        $service = new LeadConversionService();
        $project = $service->convert($lead);

        $invoices = Invoice::where('project_id', $project->id)->get();
        $this->assertCount(2, $invoices);
        
        $this->assertEquals($invoices[0]->billing_group_id, $invoices[1]->billing_group_id);
        
        $clientInvoice = $invoices->firstWhere('audience', InvoiceAudience::CLIENT);
        $partnerInvoice = $invoices->firstWhere('audience', InvoiceAudience::PARTNER);
        
        $this->assertNotNull($clientInvoice);
        $this->assertNotNull($partnerInvoice);
        
        $this->assertEquals(500000, $clientInvoice->subtotal); // 1,000,000 / 2
        $this->assertEquals(400000, $partnerInvoice->subtotal); // 800,000 / 2
        $this->assertEquals(0, $clientInvoice->discount_total);
        $this->assertEquals(0, $partnerInvoice->discount_total);
        
        $this->assertNotNull($partnerInvoice->partner_id);
        $this->assertEquals($project->client->partner_id, $partnerInvoice->partner_id);
    }

    public function test_idempotent_conversion_returns_existing_project()
    {
        $lead = $this->createLead();
        $service = new LeadConversionService();
        $project1 = $service->convert($lead);
        
        // Convert again, should return existing project
        $project2 = $service->convert($lead);
        
        $this->assertEquals($project1->id, $project2->id);
        $this->assertEquals(1, Project::count());
        $this->assertEquals(1, Client::count());
    }

    public function test_duplicate_client_name_throws_exception_if_not_forced()
    {
        $existingClient = Client::factory()->create(['company_name' => 'PT MAJU JAYA']);
        $lead = $this->createLead();
        $lead->update(['company_name' => ' pt maju jaya ']); // Similar name
        
        $service = new LeadConversionService();
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Ditemukan kandidat Klien dengan nama yang sama. Silakan tinjau dan konfirmasi.');
        
        $service->convert($lead);
    }

    public function test_force_duplicate_client_rejects_if_client_has_project()
    {
        $existingClient = Client::factory()->create(['company_name' => 'PT MAJU JAYA']);
        Project::factory()->create(['client_id' => $existingClient->id]); // Client already has project
        
        $lead = $this->createLead();
        $lead->update(['company_name' => 'PT MAJU JAYA']);
        
        $service = new LeadConversionService();
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Client yang dipilih sudah memiliki Project.');
        
        $service->convert($lead, $existingClient->id);
    }

    public function test_force_client_works_if_no_project()
    {
        $existingClient = Client::factory()->create(['company_name' => 'PT MAJU JAYA']);
        // No project for this client yet
        
        $lead = $this->createLead();
        $lead->update(['company_name' => 'PT MAJU JAYA']);
        
        $service = new LeadConversionService();
        $project = $service->convert($lead, $existingClient->id);
        
        $this->assertEquals($existingClient->id, $project->client_id);
    }
}
