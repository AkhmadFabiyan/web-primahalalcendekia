<?php

namespace Tests\Feature;

use App\Modules\Clients\Models\Client;
use App\Models\User;
use App\Modules\Projects\Enums\ProjectStatus;
use App\Modules\Projects\Enums\ProjectArchiveStatus;
use App\Modules\Projects\Enums\ArchiveVisibility;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\ProjectArchive;
use App\Modules\Projects\Services\ProjectArchiveManifestService;
use App\Modules\Projects\Services\ProjectArchiveZipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class Phase27ProjectArchiveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup Roles and Permissions
        $permissions = [
            'archives.view',
            'archives.download_internal',
            'archives.download_client',
            'archives.generate'
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        $superAdminRole = Role::firstOrCreate(['name' => 'SUPER_ADMIN']);
        $superAdminRole->givePermissionTo(Permission::all());

        $clientRole = Role::firstOrCreate(['name' => 'KLIEN']);
        $clientRole->givePermissionTo(['archives.view', 'archives.download_client']);

        Storage::fake('local');
    }

    public function test_manifest_is_generated_on_project_cancellation()
    {
        $client = Client::factory()->create();
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'status' => ProjectStatus::WAITING_ACTIVATION
        ]);
        
        $actor = User::factory()->create();
        $actor->assignRole('SUPER_ADMIN');

        $this->actingAs($actor);

        $cancelService = app(\App\Modules\Projects\Services\ProjectCancellationService::class);
        $cancelService->cancel($project, 'Alasan tes', $actor);

        $this->assertDatabaseHas('project_archives', [
            'project_id' => $project->id,
            'archive_version' => 1,
            'status' => ProjectArchiveStatus::NOT_CREATED->value,
        ]);
    }

    public function test_project_archive_manifest_collects_items_correctly()
    {
        $client = Client::factory()->create();
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'status' => ProjectStatus::COMPLETED
        ]);

        $documentType = \App\Modules\Documents\Models\DocumentType::firstOrCreate(['name' => 'KTP', 'code' => 'KTP', 'category' => '01-Administrasi']);
        $document = \App\Modules\Documents\Models\Document::create([
            'project_id' => $project->id,
            'document_type_id' => $documentType->id,
            'version' => 1,
            'is_client_visible' => true,
            'uploaded_by' => User::factory()->create()->id,
            'uploaded_at' => now(),
            'status' => \App\Modules\Documents\Enums\DocumentRecordStatus::UPLOADED,
        ]);
        
        $fakeFile = UploadedFile::fake()->create('ktp.pdf', 100);
        $document->addMedia($fakeFile)->toMediaCollection('document-file');

        $service = app(ProjectArchiveManifestService::class);
        $archive = $service->generate($project);

        $this->assertCount(1, $archive->items);
        $item = $archive->items->first();
        $this->assertEquals('KTP', $item->document_name);
        $this->assertEquals('v1', $item->document_version);
        $this->assertEquals(ArchiveVisibility::CLIENT, $item->visibility);
        $this->assertEquals('01-Administrasi', $item->category);
    }

    public function test_zip_service_creates_zip_successfully()
    {
        $client = Client::factory()->create();
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'status' => ProjectStatus::COMPLETED,
            'completed_at' => now(),
        ]);

        $archive = ProjectArchive::create([
            'project_id' => $project->id,
            'archive_version' => 1,
            'status' => ProjectArchiveStatus::NOT_CREATED,
        ]);

        $documentType = \App\Modules\Documents\Models\DocumentType::firstOrCreate(['name' => 'KTP', 'code' => 'KTP', 'category' => '01-Administrasi']);
        $document = \App\Modules\Documents\Models\Document::create([
            'project_id' => $project->id,
            'document_type_id' => $documentType->id,
            'version' => 1,
            'is_client_visible' => true,
            'uploaded_by' => User::factory()->create()->id,
            'uploaded_at' => now(),
            'status' => \App\Modules\Documents\Enums\DocumentRecordStatus::UPLOADED,
        ]);
        
        $fakeFile = UploadedFile::fake()->create('ktp.pdf', 100);
        $media = $document->addMedia($fakeFile)->toMediaCollection('document-file');

        $archive->items()->create([
            'source_type' => get_class($document),
            'source_id' => $document->id,
            'media_id' => $media->id,
            'category' => '01-Administrasi',
            'document_name' => 'KTP',
            'visibility' => ArchiveVisibility::CLIENT,
            'file_size' => 100,
            'checksum_sha256' => 'dummyhash',
        ]);

        $service = app(ProjectArchiveZipService::class);
        $service->createZip($archive, ArchiveVisibility::CLIENT);

        $this->assertEquals(ProjectArchiveStatus::READY, $archive->fresh()->status);
        $this->assertTrue($archive->hasMedia('archive-client'));
    }

    public function test_invalidates_archive_on_reopen()
    {
        $client = Client::factory()->create();
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'status' => ProjectStatus::CANCELLED
        ]);

        $archive = ProjectArchive::create([
            'project_id' => $project->id,
            'archive_version' => 1,
            'status' => ProjectArchiveStatus::NOT_CREATED,
        ]);

        $actor = User::factory()->create();
        $actor->assignRole('SUPER_ADMIN');

        $reopenService = app(\App\Modules\Projects\Services\ProjectReopeningService::class);
        $reopenService->reopen($project, 'Alasan Reopen', $actor);

        $this->assertNotNull($archive->fresh()->invalidated_at);
        $this->assertEquals(ProjectStatus::WAITING_SETTLEMENT, $project->fresh()->status);
    }
}
