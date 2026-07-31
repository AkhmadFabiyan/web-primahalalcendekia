<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Modules\Leads\Models\Lead;
use App\Modules\Clients\Models\Partner;
use App\Enums\Role;
use App\Modules\Leads\Enums\LeadStatus;
use App\Modules\Leads\Enums\PaymentScheme;
use App\Modules\Clients\Enums\ClientType;
use Livewire\Livewire;
use Spatie\Permission\Models\Role as SpatieRole;

class LeadManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Run role permission seeder
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_marketing_can_create_lead_as_draft()
    {
        $marketing = User::factory()->create();
        $marketing->assignRole(Role::MARKETING->value);
        $marketing->givePermissionTo(['leads.view', 'leads.create', 'leads.update', 'leads.change_status']);

        Livewire::actingAs($marketing)
            ->test(\App\Filament\Resources\Leads\Pages\CreateLead::class)
            ->fillForm([
                'company_name' => 'PT Test Lead',
                'client_type' => ClientType::DIRECT->value,
                'pic_name' => 'John Doe',
                'pic_phone' => '081234567890',
                'payment_scheme' => PaymentScheme::FULL_PAYMENT->value,
                'installment_count' => 1,
                'client_nominal' => 15000000,
                'marketing_id' => $marketing->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('leads', [
            'company_name' => 'PT Test Lead',
            'status' => LeadStatus::DRAFT->value,
        ]);
    }

    public function test_partner_lead_requires_partner_nominal_and_partner_data()
    {
        $marketing = User::factory()->create();
        $marketing->assignRole(Role::MARKETING->value);
        $marketing->givePermissionTo(['leads.view', 'leads.create', 'leads.update', 'leads.change_status']);

        Livewire::actingAs($marketing)
            ->test(\App\Filament\Resources\Leads\Pages\CreateLead::class)
            ->fillForm([
                'company_name' => 'PT Test Mitra',
                'client_type' => ClientType::PARTNER->value,
                'pic_name' => 'John Doe',
                'pic_phone' => '081234567890',
                'payment_scheme' => PaymentScheme::FULL_PAYMENT->value,
                'installment_count' => 1,
                'client_nominal' => 15000000,
                'marketing_id' => $marketing->id,
                // Missing partner nominal and partner data
            ])
            ->call('create')
            ->assertHasFormErrors(['partner_nominal', 'partner_name', 'partner_id']);
    }

    public function test_partner_data_conflicts_prevent_creation()
    {
        $marketing = User::factory()->create();
        $marketing->assignRole(Role::MARKETING->value);
        $marketing->givePermissionTo(['leads.view', 'leads.create', 'leads.update', 'leads.change_status']);
        
        $partner = Partner::factory()->create();

        Livewire::actingAs($marketing)
            ->test(\App\Filament\Resources\Leads\Pages\CreateLead::class)
            ->fillForm([
                'company_name' => 'PT Test Mitra 2',
                'client_type' => ClientType::PARTNER->value,
                'pic_name' => 'John Doe',
                'pic_phone' => '081234567890',
                'payment_scheme' => PaymentScheme::FULL_PAYMENT->value,
                'installment_count' => 1,
                'client_nominal' => 15000000,
                'partner_nominal' => 5000000,
                'marketing_id' => $marketing->id,
                'partner_id' => $partner->id,
                'partner_name' => 'New Partner Conflict', // Both filled
            ])
            ->call('create')
            ->assertHasFormErrors(); // Because disabled fields (or our validation) should prevent this. Wait, livewire might just ignore disabled fields. Let's see if we enforce required_without.
    }

    public function test_marketing_can_only_view_own_leads()
    {
        $marketing1 = User::factory()->create();
        $marketing1->assignRole(Role::MARKETING->value);
        $marketing1->givePermissionTo(['leads.view']);

        $marketing2 = User::factory()->create();
        
        $lead1 = Lead::factory()->create(['marketing_id' => $marketing1->id]);
        $lead2 = Lead::factory()->create(['marketing_id' => $marketing2->id]);

        Livewire::actingAs($marketing1)
            ->test(\App\Filament\Resources\Leads\Pages\ListLeads::class)
            ->assertCanSeeTableRecords([$lead1])
            ->assertCanNotSeeTableRecords([$lead2]);
    }
}
