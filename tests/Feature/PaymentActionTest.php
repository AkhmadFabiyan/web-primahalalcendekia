<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Payments\Models\Invoice;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Enums\InvoiceStatus;
use App\Modules\Payments\Enums\PaymentStatus;
use App\Modules\Payments\Enums\InvoiceAudience;
use App\Modules\Payments\Enums\InvoiceType;
use App\Modules\Payments\Services\PaymentService;
use App\Modules\Payments\Events\ActivationBillingGroupPaid;
use App\Modules\Projects\Models\Project;
use App\Modules\Clients\Models\Client;
use App\Modules\Clients\Models\Partner;
use App\Modules\Clients\Enums\ClientType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PaymentActionTest extends TestCase
{
    use RefreshDatabase;

    protected User $financeUser;
    protected User $superAdminUser;
    protected PaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $financeRole = Role::firstOrCreate(['name' => 'Finance', 'guard_name' => 'web']);
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);

        $this->financeUser = User::factory()->create();
        $this->financeUser->assignRole($financeRole);

        $this->superAdminUser = User::factory()->create();
        $this->superAdminUser->assignRole($superAdminRole);

        $this->paymentService = app(PaymentService::class);
        
        Storage::fake('public');
        Storage::fake('private');
    }

    private function createPublishedInvoice(bool $isPartner = false, float $total = 1000000)
    {
        $client = Client::factory()->create([
            'client_type' => $isPartner ? ClientType::PARTNER : ClientType::DIRECT,
            'partner_id' => $isPartner ? Partner::factory()->create()->id : null,
        ]);
        
        $project = Project::factory()->create(['client_id' => $client->id]);

        $billingGroupId = (string) \Illuminate\Support\Str::uuid();
        
        return Invoice::create([
            'project_id' => $project->id,
            'status' => InvoiceStatus::PUBLISHED,
            'invoice_type' => InvoiceType::ACTIVATION,
            'audience' => InvoiceAudience::CLIENT,
            'billing_group_id' => $billingGroupId,
            'subtotal' => $total,
            'discount_total' => 0,
            'due_date' => now()->addDays(7),
            'sequence' => 1,
            'invoice_number' => 'INV-TEST-1',
        ]);
    }

    public function test_can_create_payment_for_published_invoice()
    {
        $invoice = $this->createPublishedInvoice();
        
        $file = UploadedFile::fake()->create('proof.pdf', 100);

        $payment = $this->paymentService->createPayment($invoice, [
            'amount' => 500000,
            'payment_date' => now()->format('Y-m-d'),
            'payment_method' => 'Bank Transfer',
            'reference_number' => 'REF-123',
        ], $file->getPathname()); // Simulating the path passed by FileUpload

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'invoice_id' => $invoice->id,
            'amount' => 500000,
            'status' => PaymentStatus::PENDING->value,
        ]);
        
        // Assert sequence works
        $this->assertStringStartsWith('PAY/' . now()->year . '/000001', $payment->payment_number);
    }

    public function test_cannot_create_payment_exceeding_total()
    {
        $invoice = $this->createPublishedInvoice(false, 1000000);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Nominal pembayaran melebihi sisa tagihan');

        $this->paymentService->createPayment($invoice, [
            'amount' => 1500000,
            'payment_date' => now()->format('Y-m-d'),
            'payment_method' => 'Bank Transfer',
        ], null);
    }

    public function test_verify_partial_payment_changes_invoice_status_to_partial()
    {
        $this->actingAs($this->financeUser);
        $invoice = $this->createPublishedInvoice(false, 1000000);
        
        $payment = $this->paymentService->createPayment($invoice, [
            'amount' => 400000,
            'payment_date' => now()->format('Y-m-d'),
            'payment_method' => 'Bank Transfer',
        ], null);

        $this->paymentService->verifyPayment($payment, [
            'verification_notes' => 'OK',
        ]);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::VERIFIED->value,
            'verified_by' => $this->financeUser->id,
        ]);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => InvoiceStatus::PARTIAL->value,
        ]);
    }

    public function test_verify_full_payment_changes_invoice_status_to_paid_and_emits_event_for_direct_client()
    {
        Event::fake([ActivationBillingGroupPaid::class]);

        $this->actingAs($this->financeUser);
        $invoice = $this->createPublishedInvoice(false, 1000000);
        
        $payment = $this->paymentService->createPayment($invoice, [
            'amount' => 1000000,
            'payment_date' => now()->format('Y-m-d'),
            'payment_method' => 'Bank Transfer',
        ], null);

        $this->paymentService->verifyPayment($payment, []);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => InvoiceStatus::PAID->value,
        ]);

        Event::assertDispatched(ActivationBillingGroupPaid::class, function ($e) use ($invoice) {
            return $e->project->id === $invoice->project_id;
        });
    }

    public function test_partner_invoice_requires_both_paid_to_emit_event()
    {
        Event::fake([ActivationBillingGroupPaid::class]);
        $this->actingAs($this->financeUser);

        $invoiceClient = $this->createPublishedInvoice(true, 1000000);
        
        // Create partner invoice with same billing group
        $invoicePartner = Invoice::create([
            'project_id' => $invoiceClient->project_id,
            'status' => InvoiceStatus::PUBLISHED,
            'invoice_type' => InvoiceType::ACTIVATION,
            'audience' => InvoiceAudience::PARTNER,
            'billing_group_id' => $invoiceClient->billing_group_id,
            'subtotal' => 500000,
            'discount_total' => 0,
            'due_date' => now()->addDays(7),
            'sequence' => 2,
            'invoice_number' => 'INV-TEST-2',
        ]);

        // Pay client invoice fully
        $paymentClient = $this->paymentService->createPayment($invoiceClient, [
            'amount' => 1000000,
            'payment_date' => now()->format('Y-m-d'),
            'payment_method' => 'Cash',
        ], null);
        $this->paymentService->verifyPayment($paymentClient, []);

        // Should not dispatch yet because partner invoice is not paid
        Event::assertNotDispatched(ActivationBillingGroupPaid::class);

        // Pay partner invoice fully
        $paymentPartner = $this->paymentService->createPayment($invoicePartner, [
            'amount' => 500000,
            'payment_date' => now()->format('Y-m-d'),
            'payment_method' => 'Cash',
        ], null);
        $this->paymentService->verifyPayment($paymentPartner, []);

        // Now it should dispatch
        Event::assertDispatched(ActivationBillingGroupPaid::class);
    }

    public function test_reject_payment()
    {
        $this->actingAs($this->financeUser);
        $invoice = $this->createPublishedInvoice(false, 1000000);
        
        $payment = $this->paymentService->createPayment($invoice, [
            'amount' => 1000000,
            'payment_date' => now()->format('Y-m-d'),
            'payment_method' => 'Bank Transfer',
        ], null);

        $this->paymentService->rejectPayment($payment, [
            'rejection_reason' => 'Bukti buram',
        ]);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::REJECTED->value,
            'rejection_reason' => 'Bukti buram',
            'rejected_by' => $this->financeUser->id,
        ]);

        // Invoice status should remain PUBLISHED
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => InvoiceStatus::PUBLISHED->value,
        ]);
        
        // After rejection, available balance should be restored so we can create another payment
        $newPayment = $this->paymentService->createPayment($invoice, [
            'amount' => 1000000,
            'payment_date' => now()->format('Y-m-d'),
            'payment_method' => 'Cash',
        ], null);
        
        $this->assertNotNull($newPayment);
    }
}
