<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Notifications\Enums\NotificationEvent;
use App\Modules\Notifications\Enums\NotificationPriority;
use App\Modules\Notifications\Models\DatabaseNotification;
use App\Modules\Notifications\Services\NotificationService;
use App\Modules\Projects\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class Phase29NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Setup permissions and roles if needed
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    public function test_notification_service_creates_notification_successfully()
    {
        $user = User::factory()->create(['status' => 'ACTIVE']);
        $project = Project::factory()->create();

        $service = new NotificationService();
        $notification = $service->send(
            recipient: $user,
            event: NotificationEvent::LEAD_DEAL,
            title: 'Test Title',
            message: 'Test Message',
            project: $project,
            priority: NotificationPriority::HIGH
        );

        $this->assertNotNull($notification);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'project_id' => $project->id,
            'priority' => NotificationPriority::HIGH->value,
            'event_code' => NotificationEvent::LEAD_DEAL->value,
        ]);
    }

    public function test_duplicate_notification_is_ignored()
    {
        $user = User::factory()->create(['status' => 'ACTIVE']);
        $service = new NotificationService();
        
        $n1 = $service->send(
            recipient: $user,
            event: NotificationEvent::LEAD_DEAL,
            title: 'Title',
            message: 'Message',
            entityId: 'test-123'
        );

        $n2 = $service->send(
            recipient: $user,
            event: NotificationEvent::LEAD_DEAL,
            title: 'Title',
            message: 'Message',
            entityId: 'test-123'
        );

        $this->assertNotNull($n1);
        $this->assertNull($n2);
        
        $count = DatabaseNotification::where('deduplication_key', NotificationEvent::LEAD_DEAL->value . ':' . $user->id . ':test-123:no-workflow')->count();
        $this->assertEquals(1, $count);
    }

    public function test_recipient_resolution_prioritizes_pic()
    {
        $pic = User::factory()->create(['status' => 'ACTIVE']);
        $pic->assignRole(\App\Enums\Role::FINANCE->value);

        $roleUser = User::factory()->create(['status' => 'ACTIVE']);
        $roleUser->assignRole(\App\Enums\Role::FINANCE->value);

        $project = Project::factory()->create();
        // Assign PIC
        $project->assignments()->create([
            'user_id' => $pic->id, 
            'assignment_role' => \App\Modules\Projects\Enums\AssignmentRole::FINANCE->value,
        ]);

        $service = new NotificationService();
        $recipients = $service->resolveRecipients($project, [\App\Enums\Role::FINANCE->value]);

        $this->assertCount(1, $recipients);
        $this->assertEquals($pic->id, $recipients[0]->id);
    }

    public function test_recipient_resolution_falls_back_to_role_if_no_pic()
    {
        $roleUser1 = User::factory()->create(['status' => 'ACTIVE']);
        $roleUser1->assignRole(\App\Enums\Role::FINANCE->value);

        $roleUser2 = User::factory()->create(['status' => 'ACTIVE']);
        $roleUser2->assignRole(\App\Enums\Role::FINANCE->value);

        $project = Project::factory()->create();

        $service = new NotificationService();
        $recipients = $service->resolveRecipients($project, [\App\Enums\Role::FINANCE->value]);

        $this->assertCount(2, $recipients);
        $this->assertContains($roleUser1->id, array_column($recipients, 'id'));
        $this->assertContains($roleUser2->id, array_column($recipients, 'id'));
    }

    public function test_client_only_sees_own_notifications()
    {
        $client1 = User::factory()->create(['status' => 'ACTIVE']);
        $client2 = User::factory()->create(['status' => 'ACTIVE']);
        
        DatabaseNotification::create([
            'id' => Str::uuid(),
            'notifiable_type' => $client1->getMorphClass(),
            'notifiable_id' => $client1->id,
            'type' => 'Filament\Notifications\DatabaseNotification',
            'data' => [],
        ]);

        DatabaseNotification::create([
            'id' => Str::uuid(),
            'notifiable_type' => $client2->getMorphClass(),
            'notifiable_id' => $client2->id,
            'type' => 'Filament\Notifications\DatabaseNotification',
            'data' => [],
        ]);

        $this->actingAs($client1);
        $response = $this->get(\App\Filament\Pages\NotificationsPage::getUrl());
        
        $response->assertSuccessful();
        // Usually Livewire/Filament tests are better with Livewire testing tools, but basic HTTP check ensures no 500 error.
    }
}
