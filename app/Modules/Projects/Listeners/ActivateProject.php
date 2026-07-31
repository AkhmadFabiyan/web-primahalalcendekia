<?php

namespace App\Modules\Projects\Listeners;

use App\Modules\Payments\Events\ActivationBillingGroupPaid;
use App\Modules\Projects\Services\ProjectActivationService;
use App\Modules\Notifications\Notifications\ProjectActivatedNotification;
use App\Models\User;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class ActivateProject implements ShouldHandleEventsAfterCommit
{
    public function __construct(
        private ProjectActivationService $projectActivationService
    ) {}

    public function handle(ActivationBillingGroupPaid $event): void
    {
        $activated = $this->projectActivationService->activateProject(
            $event->projectId,
            $event->billingGroupId,
            $event->paymentId,
            $event->verifiedByUserId
        );

        if ($activated) {
            // Notify all admins
            $admins = User::role('Admin')->get();
            foreach ($admins as $admin) {
                $admin->notify(new ProjectActivatedNotification($event->projectId));
            }
        }
    }
}
