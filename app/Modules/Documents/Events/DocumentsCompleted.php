<?php

namespace App\Modules\Documents\Events;

use App\Modules\Projects\Models\Project;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DocumentsCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public Project $project)
    {
    }
}
