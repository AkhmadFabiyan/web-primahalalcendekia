<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Projects\Enums\ProjectStatus;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\Certificate;
use App\Modules\Workflows\Enums\WorkflowStatus;
use App\Modules\Workflows\Models\WorkflowStep;
use App\Modules\Payments\Models\Invoice;
use App\Modules\Payments\Enums\InvoiceType;
use App\Modules\Payments\Enums\InvoiceAudience;
use App\Modules\Payments\Enums\InvoiceStatus;
use App\Modules\Projects\Services\CertificateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class Phase24CertificateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        Role::firstOrCreate(['name' => 'Super Admin']);
        Role::firstOrCreate(['name' => 'Admin Perusahaan']);
        Role::firstOrCreate(['name' => 'Klien']);
    }

    private function createReadyProject()
    {
        $project = Project::factory()->create([
            'status' => ProjectStatus::WAITING_CERTIFICATE,
        ]);

        WorkflowStep::create([
            'project_id' => $project->id,
            'workflow_lane' => 'A',
            'step_code' => 'ENTRY_PROGRESS',
            'status' => WorkflowStatus::ENTRY_COMPLETED,
        ]);

        WorkflowStep::create([
            'project_id' => $project->id,
            'workflow_lane' => 'B',
            'step_code' => 'AUDITOR_PROGRESS',
            'status' => WorkflowStatus::AUDIT_REPORT_COMPLETED,
        ]);

        Invoice::factory()->create([
            'project_id' => $project->id,
            'billing_group_id' => \Illuminate\Support\Str::uuid(),
            'invoice_type' => InvoiceType::GOVERNMENT,
            'audience' => InvoiceAudience::CLIENT,
            'status' => InvoiceStatus::PAID,
            'subtotal' => 1000000,
            'discount_total' => 0,
        ]);

        return $project;
    }

    public function test_it_blocks_if_project_status_invalid()
    {
        $project = $this->createReadyProject();
        $project->update(['status' => ProjectStatus::WAITING_GOVERNMENT_INVOICE]);
        
        $admin = User::factory()->create();
        $admin->assignRole('Admin Perusahaan');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("bukan Menunggu Sertifikat");

        app(CertificateService::class)->issueCertificate($project, [], UploadedFile::fake()->createWithContent('sertifikat.pdf', '%PDF'), $admin);
    }
    
    public function test_it_blocks_if_invoice_negara_not_paid()
    {
        $project = $this->createReadyProject();
        Invoice::where('project_id', $project->id)->update(['status' => InvoiceStatus::PUBLISHED]);
        
        $admin = User::factory()->create();
        $admin->assignRole('Admin Perusahaan');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Invoice Negara belum dilunasi");

        app(CertificateService::class)->issueCertificate($project, [], UploadedFile::fake()->createWithContent('sertifikat.pdf', '%PDF'), $admin);
    }
    
    public function test_it_blocks_if_workflow_not_completed()
    {
        $project = $this->createReadyProject();
        WorkflowStep::where('project_id', $project->id)->where('step_code', 'ENTRY_PROGRESS')->update(['status' => WorkflowStatus::IN_PROGRESS]);
        
        $admin = User::factory()->create();
        $admin->assignRole('Admin Perusahaan');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Workflow A (Entry) belum selesai");

        app(CertificateService::class)->issueCertificate($project, [], UploadedFile::fake()->createWithContent('sertifikat.pdf', '%PDF'), $admin);
    }
    
    public function test_it_creates_certificate_and_updates_project()
    {
        Storage::fake('private');
        $project = $this->createReadyProject();
        
        $admin = User::factory()->create();
        $admin->assignRole('Admin Perusahaan');
        
        $data = [
            'certificate_number' => 'HALAL-12345',
            'issued_at' => now()->format('Y-m-d'),
            'valid_until' => now()->addYears(4)->format('Y-m-d'),
        ];
        
        $file = UploadedFile::fake()->createWithContent('sertifikat.pdf', '%PDF-1.4');
        
        $certificate = app(CertificateService::class)->issueCertificate($project, $data, $file, $admin);
        
        $this->assertNotNull($certificate);
        $this->assertEquals('HALAL-12345', $certificate->certificate_number);
        $this->assertTrue($certificate->hasMedia('certificate'));
        
        $project->refresh();
        $this->assertEquals(ProjectStatus::CERTIFICATE_ISSUED, $project->status);
        $this->assertNotNull($project->completed_at);
        
        // Cek duplikasi
        $project->update(['status' => ProjectStatus::WAITING_CERTIFICATE]);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Sertifikat sudah diterbitkan");
        app(CertificateService::class)->issueCertificate($project, $data, $file, $admin);
    }
    
    public function test_valid_until_must_be_after_issued_at()
    {
        $project = $this->createReadyProject();
        
        $admin = User::factory()->create();
        $admin->assignRole('Admin Perusahaan');
        
        $data = [
            'certificate_number' => 'HALAL-999',
            'issued_at' => now()->format('Y-m-d'),
            'valid_until' => now()->subDay()->format('Y-m-d'),
        ];
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Masa Berlaku Sertifikat harus setelah Tanggal Terbit");
        
        app(CertificateService::class)->issueCertificate($project, $data, UploadedFile::fake()->createWithContent('sertifikat.pdf', '%PDF'), $admin);
    }
}
