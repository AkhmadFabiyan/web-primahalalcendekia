<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkflowACompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $projectId;

    /**
     * Create a new event instance.
     */
    public function __construct(string $projectId)
    {
        $this->projectId = $projectId;
    }
}
