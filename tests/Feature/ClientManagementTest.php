<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use App\Modules\Clients\Enums\ClientType;
use App\Modules\Clients\Models\Client;
use App\Modules\Clients\Models\Partner;
use App\Modules\Clients\Services\ClientAccountService;
use App\Modules\Clients\Services\IdGeneratorService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Run seeders for Roles
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    public function test_id_generator_produces_correct_formats_and_shared_sequence()
    {
        $service = new IdGeneratorService;
        $year = date('Y');

        $clientId1 = $service->generateClientId();
        $this->assertEquals("PHC-HAL-{$year}-0001", $clientId1);

        $partnerCode1 = $service->generatePartnerCode();
        $this->assertEquals("PARTNER-{$year}-0001", $partnerCode1);

        $clientId2 = $service->generateClientId();
        $this->assertEquals("PHC-HAL-{$year}-0002", $clientId2);
    }

    public function test_database_constraint_for_client_type_and_partner_id()
    {
        // DIRECT client should not have partner_id
        $directClient = Client::create([
            'business_id' => 'PHC-HAL-2026-0001',
            'client_type' => ClientType::DIRECT,
            'company_name' => 'PT Direct',
            'pic_name' => 'PIC 1',
            'pic_phone' => '0812',
            'pic_email' => 'pic1@test.com',
        ]);
        $this->assertNotNull($directClient);

        // DIRECT with partner_id should fail constraint
        $partner = Partner::create([
            'partner_code' => 'PARTNER-2026-0001',
            'name' => 'Mitra A',
            'pic_name' => 'PIC Mitra',
            'phone' => '0813',
            'email' => 'mitra@test.com',
        ]);

        $this->expectException(QueryException::class);
        Client::create([
            'business_id' => 'PHC-HAL-2026-0002',
            'client_type' => ClientType::DIRECT,
            'partner_id' => $partner->id,
            'company_name' => 'PT Direct Invalid',
            'pic_name' => 'PIC 2',
            'pic_phone' => '0812',
            'pic_email' => 'pic2@test.com',
        ]);
    }

    public function test_client_account_creation_is_idempotent_and_secure()
    {
        $client = Client::create([
            'business_id' => 'PHC-HAL-2026-0003',
            'client_type' => ClientType::DIRECT,
            'company_name' => 'PT Coba Akun',
            'pic_name' => 'PIC Akun',
            'pic_phone' => '0812',
            'pic_email' => 'akun@test.com',
        ]);

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(Role::SUPER_ADMIN->value);
        $this->actingAs($superAdmin);

        $service = new ClientAccountService;
        $result = $service->createAccount($client);
        $user = $result['user'];
        $this->assertNotNull($result['password']);

        $this->assertEquals($client->id, $user->client_id);
        $this->assertTrue($user->hasRole(Role::KLIEN->value));
        $this->assertStringEndsWith('@primahalalcendekia.com', $user->email);

        // Try creating again should fail
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Klien ini sudah memiliki akun login.');
        $service->createAccount($client);
    }

    public function test_client_role_cannot_view_clients_list()
    {
        $klien = User::factory()->create(['status' => 'ACTIVE']);
        $klien->assignRole(Role::KLIEN->value);

        $this->actingAs($klien);

        // Akses route clients list
        $response = $this->get('/dashboard/clients');
        $response->assertRedirect('/dashboard'); // Sesuai dengan requirement, diredirect ke dashboard
    }

    public function test_admin_can_view_clients_list()
    {
        $admin = User::factory()->create(['status' => 'ACTIVE']);
        $admin->assignRole(Role::ADMIN->value);

        $this->actingAs($admin);

        // Akses route clients list
        $response = $this->get('/dashboard/clients');
        $response->assertStatus(200);
    }
}
