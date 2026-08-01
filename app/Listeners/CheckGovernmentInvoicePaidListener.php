<?php

namespace App\Listeners;

use App\Modules\Payments\Events\GovernmentInvoicePaid;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Enums\ProjectStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckGovernmentInvoicePaidListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(GovernmentInvoicePaid $event): void
    {
        DB::transaction(function () use ($event) {
            $project = Project::where('id', $event->projectId)->lockForUpdate()->first();

            if (!$project) {
                return;
            }

            if ($project->status === ProjectStatus::WAITING_GOVERNMENT_INVOICE) {
                $oldStatus = $project->status->value;
                $project->status = ProjectStatus::WAITING_CERTIFICATE;
                $project->save();

                activity()
                    ->performedOn($project)
                    ->event('project_status_updated')
                    ->withProperties([
                        'old_status' => $oldStatus,
                        'new_status' => ProjectStatus::WAITING_CERTIFICATE->value,
                        'invoice_id' => $event->invoiceId,
                        'payment_id' => $event->paymentId
                    ])
                    ->log("Status Project berubah menjadi WAITING_CERTIFICATE karena Invoice Negara telah lunas.");

                Log::info("Project {$project->id} transitioned to WAITING_CERTIFICATE via GovernmentInvoicePaid event.");
            }
        });
    }
}
