<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\User;
use App\Settings\CompanySettings;
use App\Settings\GeneralSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class SystemSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_super_admin_can_access_settings_page()
    {
        $superAdmin = User::factory()->create(['status' => 'ACTIVE']);
        $superAdmin->assignRole(Role::SUPER_ADMIN->value);

        $this->actingAs($superAdmin);

        $this->get('/admin/system-preference')->assertSuccessful();
    }

    public function test_non_admin_cannot_access_settings_page()
    {
        $user = User::factory()->create(['status' => 'ACTIVE']);
        // No roles assigned

        $this->actingAs($user);

        $this->get('/admin/system-preference')->assertForbidden();
    }

    public function test_super_admin_can_update_settings()
    {
        $superAdmin = User::factory()->create(['status' => 'ACTIVE']);
        $superAdmin->assignRole(Role::SUPER_ADMIN->value);

        $this->actingAs($superAdmin);

        Livewire::test(\App\Filament\Pages\SystemPreference::class)
            ->fillForm([
                'general.display_timezone' => 'Asia/Makassar',
                'general.locale' => 'en',
                'general.date_format' => 'Y-m-d',
                'company.company_name' => 'Test Company',
                'company.phone' => '123456',
                'company.email' => 'test@company.com',
                'company.address' => 'Test Address',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $generalSettings = app(GeneralSettings::class);
        $companySettings = app(CompanySettings::class);

        $this->assertEquals('Asia/Makassar', $generalSettings->display_timezone);
        $this->assertEquals('en', $generalSettings->locale);
        $this->assertEquals('Test Company', $companySettings->company_name);
    }
}
