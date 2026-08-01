<?php

namespace App\Listeners;

use App\Events\WorkflowStatusReverted;
use App\Modules\Workflows\Services\WorkflowSynchronizationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class WorkflowRevertedListener
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
    public function handle(WorkflowStatusReverted $event): void
    {
        app(WorkflowSynchronizationService::class)->revertToOperational($event->projectId);
    }
}
