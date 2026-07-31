<?php

namespace App\Modules\Payments\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Modules\Projects\Models\Project;

class ActivationBillingGroupPaid
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $projectId;
    public string $billingGroupId;
    public string $paymentId;
    public string $verifiedByUserId;

    /**
     * Create a new event instance.
     */
    public function __construct(string $projectId, string $billingGroupId, string $paymentId, string $verifiedByUserId)
    {
        $this->projectId = $projectId;
        $this->billingGroupId = $billingGroupId;
        $this->paymentId = $paymentId;
        $this->verifiedByUserId = $verifiedByUserId;
    }
}
