<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use App\Modules\Clients\Models\Client;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentUiSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_primary_filament_pages_render_for_super_admin(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN->value);
        $client = Client::factory()->create();

        $urls = [
            '/dashboard',
            '/dashboard/clients',
            "/dashboard/clients/{$client->getKey()}",
            '/dashboard/leads',
            '/dashboard/payments/invoices',
            '/dashboard/payments/transactions',
            '/dashboard/projects',
            '/dashboard/projects/project-archives',
            '/dashboard/tasks',
            '/dashboard/users',
            '/dashboard/logs/activity-logs',
            '/dashboard/settings/document-types',
            '/dashboard/system-preference',
        ];

        foreach ($urls as $url) {
            $this->actingAs($user)->get($url)->assertSuccessful();
        }
    }
}
