<?php

namespace App\Listeners;

use App\Events\WorkflowACompleted;
use App\Events\WorkflowBCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CheckWorkflowCompletionListener
{
    /**
     * Handle the events.
     */
    public function handle(WorkflowACompleted|WorkflowBCompleted $event): void
    {
        $projectId = $event->projectId ?? null;
        
        if (!$projectId) {
            return;
        }

        app(\App\Modules\Workflows\Services\WorkflowSynchronizationService::class)->synchronizeCompletion($projectId);
    }
}
