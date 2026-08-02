<?php

namespace App\Modules\Payments\Events;

use App\Modules\Projects\Models\Project;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ActivationBillingGroupPaid
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $projectId;

    public string $billingGroupId;

    public string $paymentId;

    public string $verifiedByUserId;

    public Project $project;

    /**
     * Create a new event instance.
     */
    public function __construct(string $projectId, string $billingGroupId, string $paymentId, string $verifiedByUserId)
    {
        $this->projectId = $projectId;
        $this->billingGroupId = $billingGroupId;
        $this->paymentId = $paymentId;
        $this->verifiedByUserId = $verifiedByUserId;
        $this->project = Project::findOrFail($projectId);
    }
}
