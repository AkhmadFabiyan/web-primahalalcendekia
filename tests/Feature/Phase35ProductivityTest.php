<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\RecentlyViewedRecord;
use App\Enums\Role;
use App\Modules\Clients\Models\Client;
use App\Filament\Resources\Clients\ClientResource;
use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\Payments\InvoiceResource;
use App\Filament\Resources\Payments\PaymentResource;
use App\Filament\Resources\Tasks\TaskResource;
use Filament\GlobalSearch\GlobalSearchResult;

class Phase35ProductivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_global_search_is_opt_in_and_configured_correctly()
    {
        // Testing limit
        $this->assertEquals(10, ClientResource::getGlobalSearchResultsLimit());
        $this->assertEquals(10, LeadResource::getGlobalSearchResultsLimit());
        
        // Testing that ProjectResource is not globally searchable (should not have \ true)
        $reflection = new \ReflectionClass(\App\Filament\Resources\Projects\ProjectResource::class);
        $property = $reflection->getProperty('isGloballySearchable');
        $property->setAccessible(true);
        $this->assertFalse($property->getValue(new \App\Filament\Resources\Projects\ProjectResource()));
    }

    public function test_recently_viewed_records_are_saved()
    {
        $user = User::factory()->create();
        $user->assignRole(Role::MARKETING->value);
        
        $client = Client::factory()->create();
        
        $this->actingAs($user);
        
        // Create an instance of the trait to test it
        $mock = new class {
            use \App\Traits\RecordsRecentlyViewed;
            
            public $record;
            public function getRecord() { return $this->record; }
        };
        $mock->record = $client;
        
        $mock->mountRecordsRecentlyViewed();
        
        $this->assertDatabaseHas('recently_viewed_records', [
            'user_id' => $user->id,
            'record_type' => get_class($client),
            'record_id' => $client->id,
        ]);
        
        // Max 20 check
        for ($i = 0; $i < 25; $i++) {
            $c = Client::factory()->create();
            $mock->record = $c;
            $mock->mountRecordsRecentlyViewed();
        }
        
        $count = RecentlyViewedRecord::where('user_id', $user->id)->count();
        $this->assertLessThanOrEqual(20, $count);
    }

    public function test_bulk_delete_action_is_disabled()
    {
        $bulkActions = ClientResource::table(new \Filament\Tables\Table(\Livewire\Livewire::new('filament.admin.resources.clients.pages.list-clients')))->getBulkActions();
        
        // Ensure no DeleteBulkAction exists
        foreach ($bulkActions as $action) {
            $this->assertNotInstanceOf(\Filament\Tables\Actions\DeleteBulkAction::class, $action);
            $this->assertNotInstanceOf(\Filament\Tables\Actions\ForceDeleteBulkAction::class, $action);
        }
        $this->assertTrue(true);
    }
}
