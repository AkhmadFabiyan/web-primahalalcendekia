<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkflowStatusReverted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $projectId;
    public string $workflowTrack; // ENTRY_PROGRESS, AUDITOR_PROGRESS, dll
    public string $previousStatus;
    public string $reopenedStatus;
    public string $actorId;
    public string $reason;
    public \Carbon\Carbon $occurredAt;

    /**
     * Create a new event instance.
     */
    public function __construct(
        string $projectId,
        string $workflowTrack,
        string $previousStatus,
        string $reopenedStatus,
        string $actorId,
        string $reason
    ) {
        $this->projectId = $projectId;
        $this->workflowTrack = $workflowTrack;
        $this->previousStatus = $previousStatus;
        $this->reopenedStatus = $reopenedStatus;
        $this->actorId = $actorId;
        $this->reason = $reason;
        $this->occurredAt = now();
    }
}
