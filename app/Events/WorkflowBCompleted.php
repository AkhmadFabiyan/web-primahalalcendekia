<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkflowBCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $projectId;

    public function __construct(string $projectId)
    {
        $this->projectId = $projectId;
    }
}
