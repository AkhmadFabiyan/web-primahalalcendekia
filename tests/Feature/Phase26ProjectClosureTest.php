<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Clients\Enums\ClientType;
use App\Modules\Clients\Models\Client;
use App\Modules\Payments\Enums\InvoiceStatus;
use App\Modules\Payments\Enums\InvoiceType;
use App\Modules\Payments\Models\Invoice;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Enums\PaymentStatus;
use App\Modules\Projects\Enums\ProjectStatus;
use App\Modules\Projects\Models\Certificate;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Services\ProjectCancellationService;
use App\Modules\Projects\Services\ProjectReopeningService;
use App\Modules\Workflows\Enums\TaskStatus;
use App\Modules\Workflows\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase26ProjectClosureTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $adminPerusahaan;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Admin Perusahaan', 'guard_name' => 'web']);
        
        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('Super Admin');
        
        $this->adminPerusahaan = User::factory()->create();
        $this->adminPerusahaan->assignRole('Admin Perusahaan');

        $client = Client::factory()->create(['client_type' => ClientType::DIRECT]);
        $this->project = Project::factory()->create([
            'client_id' => $client->id,
            'status' => ProjectStatus::WAITING_ACTIVATION,
        ]);
    }

    public function test_project_cancellation_success()
    {
        $service = app(ProjectCancellationService::class);
        $service->cancel($this->project, 'Klien membatalkan', $this->adminPerusahaan);

        $this->project->refresh();
        $this->assertEquals(ProjectStatus::CANCELLED, $this->project->status);
        $this->assertEquals('Klien membatalkan', $this->project->cancellation_reason);
        $this->assertEquals($this->adminPerusahaan->id, $this->project->cancelled_by);
        $this->assertEquals(ProjectStatus::WAITING_ACTIVATION->value, $this->project->cancelled_from_status);
    }

    public function test_project_cancellation_cascade_invoices()
    {
        // Invoice Draft
        $draftInvoice = Invoice::factory()->create([
            'project_id' => $this->project->id,
            'invoice_type' => InvoiceType::ACTIVATION,
            'status' => InvoiceStatus::DRAFT,
            'billing_group_id' => \Illuminate\Support\Str::uuid(),
            'audience' => 'CLIENT',
            'subtotal' => 1000,
        ]);
        
        // Invoice Published
        $publishedInvoice = Invoice::factory()->create([
            'project_id' => $this->project->id,
            'invoice_type' => InvoiceType::ACTIVATION,
            'status' => InvoiceStatus::PUBLISHED,
            'sequence' => 2,
            'billing_group_id' => \Illuminate\Support\Str::uuid(),
            'audience' => 'CLIENT',
            'subtotal' => 1000,
        ]);
        
        // Invoice Paid
        $paidInvoice = Invoice::factory()->create([
            'project_id' => $this->project->id,
            'invoice_type' => InvoiceType::ACTIVATION,
            'status' => InvoiceStatus::PAID,
            'sequence' => 3,
            'billing_group_id' => \Illuminate\Support\Str::uuid(),
            'audience' => 'CLIENT',
            'subtotal' => 1000,
        ]);

        $service = app(ProjectCancellationService::class);
        $service->cancel($this->project, 'Batal', $this->adminPerusahaan);

        $this->assertEquals(InvoiceStatus::CANCELLED, $draftInvoice->refresh()->status);
        $this->assertEquals(InvoiceStatus::CANCELLED, $publishedInvoice->refresh()->status);
        $this->assertEquals(InvoiceStatus::PAID, $paidInvoice->refresh()->status);
    }

    public function test_project_cannot_be_cancelled_if_has_certificate()
    {
        Certificate::create([
            'project_id' => $this->project->id,
            'certificate_number' => '123456',
            'issued_at' => now(),
            'uploaded_by' => $this->superAdmin->id,
        ]);
        
        $service = app(ProjectCancellationService::class);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Project yang telah menerbitkan Sertifikat tidak dapat dibatalkan melalui aksi normal.');
        
        $service->cancel($this->project, 'Batal', $this->adminPerusahaan);
    }

    public function test_project_locked_trait_prevents_updates()
    {
        $this->project->update(['status' => ProjectStatus::COMPLETED]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Data tidak dapat diubah karena Project telah berstatus Selesai atau Dibatalkan. Silakan Buka Kembali Project terlebih dahulu melalui action resmi.');
        
        $this->project->update(['client_nominal' => 9999]);
    }

    public function test_project_locked_trait_prevents_invoice_updates()
    {
        $invoice = Invoice::factory()->create([
            'project_id' => $this->project->id, 
            'invoice_type' => InvoiceType::ACTIVATION,
            'billing_group_id' => \Illuminate\Support\Str::uuid(),
            'audience' => 'CLIENT',
            'subtotal' => 1000,
            'status' => InvoiceStatus::DRAFT,
        ]);
        
        $this->project->update(['status' => ProjectStatus::COMPLETED]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Data tidak dapat diubah karena Project telah berstatus Selesai atau Dibatalkan. Silakan Buka Kembali Project terlebih dahulu melalui action resmi.');
        
        $invoice->update(['status' => InvoiceStatus::CANCELLED]);
    }

    public function test_project_locked_trait_prevents_payment_updates()
    {
        $invoice = Invoice::factory()->create([
            'project_id' => $this->project->id, 
            'invoice_type' => InvoiceType::ACTIVATION,
            'billing_group_id' => \Illuminate\Support\Str::uuid(),
            'audience' => 'CLIENT',
            'subtotal' => 1000,
            'status' => InvoiceStatus::DRAFT,
        ]);
        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'payment_number' => 'PAY-001',
            'payment_method' => 'MANUAL',
            'amount' => 1000,
            'payment_date' => now(),
            'status' => PaymentStatus::PENDING,
        ]);

        $this->project->update(['status' => ProjectStatus::COMPLETED]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Data tidak dapat diubah karena Project telah berstatus Selesai atau Dibatalkan. Silakan Buka Kembali Project terlebih dahulu melalui action resmi.');
        
        $payment->update(['status' => PaymentStatus::REJECTED]);
    }

    public function test_project_reopening_restores_status_and_unlocks()
    {
        $this->project->update([
            'status' => ProjectStatus::CANCELLED,
            'cancelled_from_status' => ProjectStatus::WAITING_ACTIVATION->value
        ]);

        $service = app(ProjectReopeningService::class);
        $service->reopen($this->project, 'Buka lagi', $this->superAdmin);

        $this->project->refresh();
        $this->assertEquals(ProjectStatus::WAITING_ACTIVATION, $this->project->status);
        $this->assertNull($this->project->cancelled_at);
        $this->assertNull($this->project->cancellation_reason);
        
        // Ensure unlocked
        $this->project->update(['client_nominal' => 9999]);
        $this->assertEquals(9999, $this->project->client_nominal);
    }
}
