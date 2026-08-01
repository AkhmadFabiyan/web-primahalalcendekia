<?php

namespace App\Modules\Projects\Jobs;

use App\Modules\Projects\Enums\ArchiveVisibility;
use App\Modules\Projects\Models\ProjectArchive;
use App\Modules\Projects\Services\ProjectArchiveZipService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateProjectArchiveZipJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ProjectArchive $archive,
        public ArchiveVisibility $visibility
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ProjectArchiveZipService $service): void
    {
        $service->createZip($this->archive, $this->visibility);
    }
}
