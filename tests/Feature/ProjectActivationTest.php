<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Enums\ProjectStatus;
use App\Modules\Clients\Models\Client;
use App\Modules\Payments\Models\Invoice;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Enums\InvoiceType;
use App\Modules\Payments\Enums\InvoiceStatus;
use App\Modules\Payments\Enums\PaymentStatus;
use App\Modules\Workflows\Models\WorkflowStep;
use App\Modules\Workflows\Models\WorkflowHistory;
use App\Models\User;
use App\Modules\Payments\Events\ActivationBillingGroupPaid;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use App\Modules\Notifications\Notifications\ProjectActivatedNotification;

class ProjectActivationTest extends TestCase
{
    use RefreshDatabase;

    protected User $financeUser;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Finance']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Admin']);
        
        $this->financeUser = User::factory()->create();
        $this->financeUser->assignRole('Finance');

        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('Admin');
    }

    public function test_direct_client_project_activates_after_invoice_paid()
    {
        Event::fake([ActivationBillingGroupPaid::class]);

        $client = Client::factory()->create(['partner_id' => null]);
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'status' => ProjectStatus::WAITING_ACTIVATION
        ]);

        $billingGroupId = \Illuminate\Support\Str::uuid()->toString();

        $invoice = Invoice::factory()->create([
            'project_id' => $project->id,
            'invoice_type' => InvoiceType::ACTIVATION,
            'audience' => \App\Modules\Payments\Enums\InvoiceAudience::CLIENT,
            'status' => InvoiceStatus::PUBLISHED,
            'billing_group_id' => $billingGroupId,
            'subtotal' => 1000,
            'discount_total' => 0
        ]);

        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'payment_number' => 'PAY/2026/000001',
            'payment_date' => now(),
            'payment_method' => 'Transfer',
            'amount' => 1000,
            'status' => PaymentStatus::PENDING
        ]);

        // Finance verifies payment
        $this->actingAs($this->financeUser);
        
        $paymentService = app(\App\Modules\Payments\Services\PaymentService::class);
        $paymentService->verifyPayment($payment, ['verification_notes' => 'Tested']);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => InvoiceStatus::PAID->value
        ]);

        Event::assertDispatched(ActivationBillingGroupPaid::class);
    }

    public function test_listener_activates_project_and_creates_workflow_steps()
    {
        Notification::fake();

        $client = Client::factory()->create(['partner_id' => null]);
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'status' => ProjectStatus::WAITING_ACTIVATION
        ]);

        $billingGroupId = \Illuminate\Support\Str::uuid()->toString();

        $invoice = Invoice::factory()->create([
            'project_id' => $project->id,
            'invoice_type' => InvoiceType::ACTIVATION,
            'audience' => \App\Modules\Payments\Enums\InvoiceAudience::CLIENT,
            'status' => InvoiceStatus::PAID,
            'billing_group_id' => $billingGroupId,
            'subtotal' => 1000,
            'discount_total' => 0
        ]);

        $paymentId = \Illuminate\Support\Str::uuid()->toString();

        // Trigger listener directly
        $event = new ActivationBillingGroupPaid($project->id, $billingGroupId, $paymentId, $this->financeUser->id);
        
        $listener = app(\App\Modules\Projects\Listeners\ActivateProject::class);
        $listener->handle($event);

        // Assert project status updated
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'status' => ProjectStatus::ACTIVE->value
        ]);
        $project->refresh();
        $this->assertNotNull($project->activated_at);

        // Assert workflow steps created
        $this->assertDatabaseCount('workflow_steps', 3);
        $this->assertDatabaseHas('workflow_steps', [
            'project_id' => $project->id,
            'track_code' => 'ENTRY',
            'status' => 'ENTRY_NOT_STARTED'
        ]);

        // Assert workflow histories created
        $this->assertDatabaseCount('workflow_histories', 3);
        $this->assertDatabaseHas('workflow_histories', [
            'project_id' => $project->id,
            'from_status' => null,
            'to_status' => 'ENTRY_NOT_STARTED',
            'actor_id' => $this->financeUser->id
        ]);

        // Assert activity log
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Project::class,
            'subject_id' => $project->id,
            'causer_type' => User::class,
            'causer_id' => $this->financeUser->id,
            'description' => 'Project diaktifkan otomatis setelah pembayaran aktivasi terpenuhi'
        ]);

        // Assert notification sent to admin
        Notification::assertSentTo(
            [$this->adminUser], 
            ProjectActivatedNotification::class
        );
    }
}
