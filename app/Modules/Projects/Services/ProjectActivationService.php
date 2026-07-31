<?php

namespace App\Modules\Projects\Services;

use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Enums\ProjectStatus;
use App\Modules\Payments\Models\Invoice;
use App\Modules\Payments\Enums\InvoiceType;
use App\Modules\Payments\Enums\InvoiceStatus;
use App\Models\User;
use App\Modules\Workflows\Services\WorkflowInitializationService;
use App\Modules\Workflows\Services\TaskService;
use App\Modules\Projects\Models\ProjectAssignment;
use App\Modules\Projects\Enums\AssignmentRole;
use App\Modules\Documents\Services\DocumentService;
use Exception;
use Illuminate\Support\Facades\DB;

class ProjectActivationService
{
    public function __construct(
        private WorkflowInitializationService $workflowInitializationService,
        private TaskService $taskService,
        private DocumentService $documentService
    ) {}

    /**
     * @return bool True if activated now, False if already active (idempotent), throws Exception on failure.
     */
    public function activateProject(string $projectId, string $billingGroupId, string $paymentId, string $verifiedByUserId): bool
    {
        return DB::transaction(function () use ($projectId, $billingGroupId, $paymentId, $verifiedByUserId) {
            $project = Project::where('id', $projectId)->lockForUpdate()->first();

            if (!$project) {
                throw new Exception("Project tidak ditemukan.");
            }

            if ($project->status === ProjectStatus::ACTIVE || $project->status === ProjectStatus::OPERATIONAL) {
                // Idempotent return
                return false;
            }

            if ($project->status === ProjectStatus::CANCELLED) {
                throw new Exception("Transisi ilegal: Project dibatalkan tidak dapat diaktifkan.");
            }

            if ($project->status !== ProjectStatus::WAITING_ACTIVATION) {
                throw new Exception("Transisi ilegal: Project tidak dalam status WAITING_ACTIVATION.");
            }

            // Lock the billing group invoices
            $invoices = Invoice::where('billing_group_id', $billingGroupId)
                ->where('project_id', $projectId)
                ->lockForUpdate()
                ->get();

            if ($invoices->isEmpty()) {
                throw new Exception("Tidak ada invoice pada billing group ini.");
            }

            foreach ($invoices as $invoice) {
                if ($invoice->invoice_type !== InvoiceType::ACTIVATION) {
                    throw new Exception("Billing group ini bukan untuk invoice aktivasi.");
                }
                
                if ($invoice->status === InvoiceStatus::CANCELLED) {
                    throw new Exception("Invoice dalam billing group ini telah dibatalkan.");
                }

                if ($invoice->status !== InvoiceStatus::PAID) {
                    throw new Exception("Pembayaran aktivasi belum lengkap.");
                }
            }

            // All validations passed, activate project
            $project->status = ProjectStatus::ACTIVE;
            $project->activated_at = now();
            $project->save();

            // Create initial workflow trackers
            $this->workflowInitializationService->initializeForProject($project, $verifiedByUserId);
            
            // Snapshot active document types
            $this->documentService->snapshotRequirements($project);

            // Check if Admin is assigned, if so create initial task
            $adminAssignment = ProjectAssignment::where('project_id', $project->id)
                ->where('assignment_role', AssignmentRole::ADMIN->value)
                ->whereNull('ended_at')
                ->first();
                
            if ($adminAssignment) {
                $this->taskService->ensureInitialOperationalTask($project, clone $adminAssignment->user);
            }

            // Spatie Activity Log specifically configured
            activity()
                ->performedOn($project)
                ->causedBy(User::find($verifiedByUserId))
                ->withProperties([
                    'source' => 'SYSTEM_EVENT',
                    'event' => 'ActivationBillingGroupPaid',
                    'billing_group_id' => $billingGroupId,
                    'payment_id' => $paymentId,
                    'verified_by' => $verifiedByUserId,
                    'from_status' => ProjectStatus::WAITING_ACTIVATION->value,
                    'to_status' => ProjectStatus::ACTIVE->value,
                ])
                ->log('Project diaktifkan otomatis setelah pembayaran aktivasi terpenuhi');

            return true;
        });
    }
}
