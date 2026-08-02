<?php

namespace Tests\Feature;

use App\Filament\Widgets\FinanceKpiWidget;
use App\Filament\Widgets\FinanceRevenueChart;
use App\Models\User;
use App\Modules\Clients\Enums\ClientType;
use App\Modules\Clients\Models\Client;
use App\Modules\Payments\Enums\InvoiceAudience;
use App\Modules\Payments\Enums\InvoiceStatus;
use App\Modules\Payments\Enums\InvoiceType;
use App\Modules\Payments\Enums\PaymentStatus;
use App\Modules\Payments\Models\Invoice;
use App\Modules\Payments\Models\Payment;
use App\Modules\Projects\Models\Project;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class Phase32FinanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Permission::create(['name' => 'dashboard.finance.view', 'guard_name' => 'web']);
    }

    public function test_only_users_with_permission_can_view_finance_widgets()
    {
        $userWithoutPermission = User::factory()->create();

        $userWithPermission = User::factory()->create();
        $userWithPermission->givePermissionTo('dashboard.finance.view');

        // Without permission
        $this->actingAs($userWithoutPermission)
            ->get('/dashboard')
            ->assertSuccessful() // Should not 403, just hide widgets
            ->assertDontSeeLivewire(FinanceKpiWidget::class)
            ->assertDontSeeLivewire(FinanceRevenueChart::class);

        // With permission
        $this->actingAs($userWithPermission)
            ->get('/dashboard')
            ->assertSuccessful()
            ->assertSeeLivewire(FinanceKpiWidget::class);
    }

    public function test_kas_masuk_only_counts_verified_commercial_payments()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('dashboard.finance.view');

        $client = Client::factory()->create(['client_type' => ClientType::DIRECT->value]);
        $project = Project::factory()->create(['client_id' => $client->id]);

        // Commercial Invoice
        $invoice = Invoice::factory()->create([
            'project_id' => $project->id,
            'audience' => InvoiceAudience::CLIENT->value,
            'invoice_type' => InvoiceType::ACTIVATION->value,
            'status' => InvoiceStatus::PUBLISHED->value,
            'subtotal' => 1000000,
            'discount_total' => 0,
            'issued_at' => Carbon::now()->subDays(2),
        ]);

        // Verified Payment (should be counted)
        Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 500000,
            'status' => PaymentStatus::VERIFIED->value,
            'payment_date' => Carbon::now()->subDays(1),
        ]);

        // Pending Payment (should NOT be counted in revenue)
        Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 200000,
            'status' => PaymentStatus::PENDING->value,
        ]);

        // Government Invoice
        $govInvoice = Invoice::factory()->create([
            'project_id' => $project->id,
            'audience' => InvoiceAudience::CLIENT->value,
            'invoice_type' => InvoiceType::GOVERNMENT->value,
            'status' => InvoiceStatus::PUBLISHED->value,
            'subtotal' => 300000,
            'discount_total' => 0,
        ]);

        Payment::factory()->create([
            'invoice_id' => $govInvoice->id,
            'amount' => 300000,
            'status' => PaymentStatus::VERIFIED->value,
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertSee('Rp 500.000'); // Should only sum the verified commercial one
    }

    public function test_outstanding_does_not_count_draft_and_cancelled()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('dashboard.finance.view');

        $client = Client::factory()->create(['client_type' => ClientType::DIRECT->value]);
        $project = Project::factory()->create(['client_id' => $client->id]);

        // Published (should count)
        Invoice::factory()->create([
            'project_id' => $project->id,
            'audience' => InvoiceAudience::CLIENT->value,
            'invoice_type' => InvoiceType::ACTIVATION->value,
            'status' => InvoiceStatus::PUBLISHED->value,
            'subtotal' => 1000000,
            'discount_total' => 0,
            'issued_at' => Carbon::now()->subDays(2),
        ]);

        // Draft (should NOT count)
        Invoice::factory()->create([
            'project_id' => $project->id,
            'audience' => InvoiceAudience::CLIENT->value,
            'invoice_type' => InvoiceType::INSTALLMENT->value,
            'status' => InvoiceStatus::DRAFT->value,
            'subtotal' => 2000000,
            'discount_total' => 0,
        ]);

        // Cancelled (should NOT count)
        Invoice::factory()->create([
            'project_id' => $project->id,
            'audience' => InvoiceAudience::CLIENT->value,
            'invoice_type' => InvoiceType::SETTLEMENT->value,
            'status' => InvoiceStatus::CANCELLED->value,
            'subtotal' => 3000000,
            'discount_total' => 0,
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertSee('Rp 1.000.000');
    }
}
