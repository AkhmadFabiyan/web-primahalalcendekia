<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Clients\Models\Client;
use App\Modules\Projects\Enums\ProjectStatus;
use App\Modules\Projects\Models\Project;
use App\Modules\Workflows\Enums\WorkflowStatus;
use App\Modules\Workflows\Models\WorkflowStep;
use App\Modules\Payments\Models\Invoice;
use App\Modules\Payments\Enums\InvoiceType;
use App\Modules\Payments\Enums\InvoiceAudience;
use App\Modules\Payments\Enums\InvoiceStatus;
use App\Modules\Payments\Enums\PaymentStatus;
use App\Modules\Payments\Services\GovernmentInvoiceService;
use App\Modules\Payments\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class Phase23GovernmentInvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup Roles
        Role::firstOrCreate(['name' => 'Super Admin']);
        Role::firstOrCreate(['name' => 'Admin Perusahaan']);
        Role::firstOrCreate(['name' => 'Finance']);
    }

    public function test_it_blocks_government_invoice_creation_if_project_status_not_waiting_invoice()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin Perusahaan');

        $project = Project::factory()->create([
            'status' => ProjectStatus::OPERATIONAL,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("bukan Menunggu Invoice Negara");

        app(GovernmentInvoiceService::class)->create($project->id, $admin, [
            'invoice_number' => 'INV-GOV-01',
            'nominal' => 1000000,
            'due_date' => now()->addDays(7)->format('Y-m-d'),
            'file' => UploadedFile::fake()->create('inv.pdf', 100, 'application/pdf')
        ]);
    }

    public function test_it_blocks_government_invoice_creation_if_workflow_not_completed()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin Perusahaan');

        $project = Project::factory()->create([
            'status' => ProjectStatus::WAITING_GOVERNMENT_INVOICE,
        ]);

        // Missing Workflow A and B
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Workflow A (Entry) belum selesai");

        app(GovernmentInvoiceService::class)->create($project->id, $admin, [
            'invoice_number' => 'INV-GOV-01',
            'nominal' => 1000000,
            'due_date' => now()->addDays(7)->format('Y-m-d'),
            'file' => UploadedFile::fake()->create('inv.pdf', 100, 'application/pdf')
        ]);
    }

    public function test_it_creates_government_invoice_successfully_and_prevents_duplicate()
    {
        Storage::fake('local');
        
        $admin = User::factory()->create();
        $admin->assignRole('Admin Perusahaan');

        $project = Project::factory()->create([
            'status' => ProjectStatus::WAITING_GOVERNMENT_INVOICE,
        ]);

        WorkflowStep::create([
            'project_id' => $project->id,
            'workflow_lane' => 'A',
            'step_code' => 'ENTRY_PROGRESS',
            'status' => WorkflowStatus::ENTRY_COMPLETED,
        ]);

        WorkflowStep::create([
            'project_id' => $project->id,
            'workflow_lane' => 'B',
            'step_code' => 'AUDITOR_PROGRESS',
            'status' => WorkflowStatus::AUDIT_REPORT_COMPLETED,
        ]);

        $data = [
            'invoice_number' => 'INV-BPJPH-2026',
            'nominal' => 1500000,
            'due_date' => now()->addDays(7)->format('Y-m-d'),
            'file' => UploadedFile::fake()->createWithContent('inv.pdf', '%PDF-1.4')
        ];

        $invoice = app(GovernmentInvoiceService::class)->create($project->id, $admin, $data);

        $this->assertEquals(InvoiceType::GOVERNMENT, $invoice->invoice_type);
        $this->assertEquals(InvoiceAudience::CLIENT, $invoice->audience);
        $this->assertEquals(1500000, $invoice->subtotal);
        $this->assertEquals(InvoiceStatus::PUBLISHED, $invoice->status);
        $this->assertTrue($invoice->hasMedia('government-invoice-document'));

        // Try duplicate
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Invoice Negara sudah dibuat");
        app(GovernmentInvoiceService::class)->create($project->id, $admin, $data);
    }
    
    public function test_partial_and_full_verification_transitions_project_to_waiting_certificate()
    {
        Storage::fake('local');
        
        $finance = User::factory()->create();
        $finance->assignRole('Finance');
        
        $project = Project::factory()->create([
            'status' => ProjectStatus::WAITING_GOVERNMENT_INVOICE,
        ]);
        
        $invoice = Invoice::factory()->create([
            'project_id' => $project->id,
            'billing_group_id' => \Illuminate\Support\Str::uuid(),
            'invoice_type' => InvoiceType::GOVERNMENT,
            'audience' => InvoiceAudience::CLIENT,
            'status' => InvoiceStatus::PUBLISHED,
            'subtotal' => 2000000,
            'discount_total' => 0,
        ]);
        
        // 1. Catat pembayaran 1 (Partial 1jt)
        $payment1 = app(PaymentService::class)->createPayment($invoice, [
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 1000000,
            'payment_method' => 'Transfer BCA',
        ], UploadedFile::fake()->image('bukti1.jpg'));
        
        $this->actingAs($finance);
        
        // Verifikasi pembayaran 1
        app(PaymentService::class)->verifyPayment($payment1, []);
        
        $invoice->refresh();
        $project->refresh();
        
        $this->assertEquals(InvoiceStatus::PARTIAL, $invoice->status);
        $this->assertEquals(ProjectStatus::WAITING_GOVERNMENT_INVOICE, $project->status);
        
        // 2. Catat pembayaran 2 (Sisa 1jt)
        $payment2 = app(PaymentService::class)->createPayment($invoice, [
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 1000000,
            'payment_method' => 'Transfer Mandiri',
        ], UploadedFile::fake()->image('bukti2.jpg'));
        
        // Verifikasi pembayaran 2
        app(PaymentService::class)->verifyPayment($payment2, []);
        
        $invoice->refresh();
        $project->refresh();
        
        $this->assertEquals(InvoiceStatus::PAID, $invoice->status);
        $this->assertEquals(ProjectStatus::WAITING_CERTIFICATE, $project->status);
    }
}
