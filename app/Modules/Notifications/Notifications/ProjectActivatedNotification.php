<?php

namespace App\Modules\Notifications\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Modules\Projects\Models\Project;

class ProjectActivatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $projectId
    ) {}

    public function via(object $notifiable): array
    {
        return ['database']; // Laravel Database Notification
    }

    public function toDatabase(object $notifiable): array
    {
        $project = Project::find($this->projectId);
        $projectTitle = $project ? $project->title : 'Unknown';

        return \Filament\Notifications\Notification::make()
            ->title("Project Aktif")
            ->body("Project {$projectTitle} telah aktif dan siap diproses.")
            ->success()
            ->getDatabaseMessage();
    }
}
