<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Documents\Enums\DocumentModuleStatus;
use App\Modules\Documents\Enums\DocumentRecordStatus;
use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Models\DocumentType;
use App\Modules\Documents\Models\ProjectDocumentRequirement;
use App\Modules\Documents\Services\DocumentService;
use App\Modules\Projects\Enums\ProjectStatus;
use App\Modules\Projects\Models\Project;
use App\Modules\Workflows\Models\WorkflowStep;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class Phase15DocumentAdministrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed document types
        $this->seed(\Database\Seeders\DocumentTypeSeeder::class);
    }

    public function test_snapshot_requirements_called_on_activation()
    {
        $project = Project::factory()->create([
            'status' => ProjectStatus::WAITING_ACTIVATION,
        ]);

        $service = app(DocumentService::class);
        $service->snapshotRequirements($project);

        $this->assertDatabaseHas('project_document_requirements', [
            'project_id' => $project->id,
            'is_required' => true,
        ]);

        $this->assertDatabaseHas('workflow_steps', [
            'project_id' => $project->id,
            'step_code' => 'DOCUMENT_ADMINISTRATION',
            'status' => DocumentModuleStatus::NOT_STARTED->value,
        ]);
    }

    public function test_upload_document_changes_status_to_in_progress_and_complete()
    {
        $project = Project::factory()->create(['status' => ProjectStatus::ACTIVE]);
        $user = User::factory()->create();

        $service = app(DocumentService::class);
        $service->snapshotRequirements($project);

        // Upload first doc
        $reqs = ProjectDocumentRequirement::where('project_id', $project->id)->where('is_required', true)->get();
        $firstReq = $reqs->first();

        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');
        
        $document = $service->uploadDocument($project, $firstReq->document_type_id, $file, $user);

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'project_id' => $project->id,
            'status' => DocumentRecordStatus::UPLOADED->value,
        ]);

        $this->assertDatabaseHas('workflow_steps', [
            'project_id' => $project->id,
            'step_code' => 'DOCUMENT_ADMINISTRATION',
            'status' => DocumentModuleStatus::IN_PROGRESS->value,
        ]);

        // Mock remaining uploads to test COMPLETE status
        foreach ($reqs->skip(1) as $req) {
            $newFile = UploadedFile::fake()->create('test2.pdf', 100, 'application/pdf');
            $service->uploadDocument($project, $req->document_type_id, $newFile, $user);
        }

        $this->assertDatabaseHas('workflow_steps', [
            'project_id' => $project->id,
            'step_code' => 'DOCUMENT_ADMINISTRATION',
            'status' => DocumentModuleStatus::COMPLETE->value,
        ]);
    }
}
