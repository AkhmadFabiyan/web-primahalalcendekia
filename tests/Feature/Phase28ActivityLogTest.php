<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Logs\Models\Activity;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Enums\ProjectStatus;
use App\Modules\Projects\Services\ProjectCancellationService;
use App\Modules\Projects\Services\ProjectCompletionService;
use App\Modules\Projects\Services\ProjectReopeningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class Phase28ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Setup initial data if needed, like roles and permissions
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    public function test_activity_log_is_append_only()
    {
        $user = User::factory()->create();
        
        $activity = activity()
            ->causedBy($user)
            ->event('test_event')
            ->log('Test log');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Activity Log is append-only and cannot be updated.');
        $activity->update(['description' => 'Updated']);
    }

    public function test_activity_log_cannot_be_deleted()
    {
        $user = User::factory()->create();
        
        $activity = activity()
            ->causedBy($user)
            ->event('test_event')
            ->log('Test log');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Activity Log is append-only and cannot be deleted.');
        $activity->delete();
    }

    public function test_project_cancellation_creates_correct_activity_log()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['status' => ProjectStatus::OPERATIONAL]);

        $service = app(ProjectCancellationService::class);
        $service->cancel($project, 'Test reason', $user);

        $activity = Activity::where('event', 'cancelled')->first();

        $this->assertNotNull($activity);
        $this->assertEquals($project->id, $activity->project_id);
        $this->assertFalse($activity->is_client_visible);
        $this->assertEquals($user->id, $activity->causer_id);
        
        $properties = $activity->properties;
        $this->assertEquals(ProjectStatus::OPERATIONAL->value, $properties['old']['status']);
        $this->assertEquals(ProjectStatus::CANCELLED->value, $properties['attributes']['status']);
        $this->assertEquals('Test reason', $properties['context']['reason']);
    }

    public function test_project_completion_creates_client_visible_activity_log()
    {
        $project = Project::factory()->create(['status' => ProjectStatus::CERTIFICATE_ISSUED]);

        $mock = \Mockery::mock('alias:\App\Modules\Projects\Services\ProjectClosureReadinessService');
        $mock->shouldReceive('evaluate')->andReturn([]);
        $mock->shouldReceive('isReady')->andReturn(true);

        $service = app(ProjectCompletionService::class);
        $service->checkCompletion($project);

        $activity = Activity::where('event', 'completed')->first();

        $this->assertNotNull($activity);
        $this->assertEquals($project->id, $activity->project_id);
        $this->assertTrue($activity->is_client_visible);
        
        $properties = $activity->properties;
        $this->assertEquals(ProjectStatus::CERTIFICATE_ISSUED->value, $properties['old']['status']);
        $this->assertEquals(ProjectStatus::COMPLETED->value, $properties['attributes']['status']);
    }

    public function test_filament_resource_is_read_only()
    {
        // Tests the Policy
        $user = User::factory()->create();
        $user->assignRole(\App\Enums\Role::ADMIN->value);
        
        $activity = activity()->event('test')->log('test log');
        
        $policy = new \App\Policies\ActivityPolicy();
        
        $this->assertFalse($policy->update($user, $activity));
        $this->assertFalse($policy->delete($user, $activity));
        $this->assertFalse($policy->create($user));
    }
}
