<?php

namespace App\Modules\Notifications\Services;

use App\Models\User;
use App\Modules\Notifications\Enums\NotificationEvent;
use App\Modules\Notifications\Enums\NotificationPriority;
use App\Modules\Notifications\Models\DatabaseNotification;
use App\Modules\Projects\Models\Project;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificationService
{
    /**
     * Send a notification directly.
     * Ensure this is called from a queued job/listener.
     */
    public function send(
        User $recipient,
        NotificationEvent $event,
        string $title,
        string $message,
        ?Project $project = null,
        string $status = 'info',
        string $icon = 'heroicon-o-information-circle',
        NotificationPriority $priority = NotificationPriority::MEDIUM,
        ?string $route = null,
        ?string $entityId = null,
        ?string $workflowId = null
    ): ?DatabaseNotification {
        if ($recipient->status !== 'ACTIVE') {
            return null; // Do not send to inactive users
        }

        // Generate deduplication key
        // event_code:user-uuid:entity-id:workflow-uuid
        $parts = [
            $event->value,
            $recipient->id,
            $entityId ?? 'no-entity',
            $workflowId ?? 'no-workflow'
        ];
        $dedupKey = implode(':', $parts);

        try {
            return DB::transaction(function () use ($recipient, $event, $title, $message, $project, $status, $icon, $priority, $route, $dedupKey) {
                // Check deduplication
                $exists = DatabaseNotification::where('deduplication_key', $dedupKey)->lockForUpdate()->exists();
                if ($exists) {
                    return null; // Duplicate
                }

                $data = [
                    'format' => 'filament',
                    'title' => $title,
                    'body' => $message,
                    'status' => $status,
                    'icon' => $icon,
                ];

                if ($route) {
                    // For Filament native format, action URL is complex, but we can set deep_link custom data or native actions.
                    // Actually, Filament notification click action uses actions array.
                    // Simple deep link:
                    $data['actions'] = [
                        [
                            'name' => 'view',
                            'label' => 'Lihat',
                            'url' => $route,
                            'shouldOpenInNewTab' => false,
                        ]
                    ];
                }

                $notification = new DatabaseNotification();
                $notification->id = (string) Str::uuid();
                $notification->type = 'Filament\\Notifications\\DatabaseNotification';
                $notification->notifiable_type = $recipient->getMorphClass();
                $notification->notifiable_id = $recipient->id;
                $notification->project_id = $project?->id;
                $notification->priority = $priority->value;
                $notification->event_code = $event->value;
                $notification->deduplication_key = $dedupKey;
                $notification->data = $data;
                $notification->save();

                return $notification;
            });
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            // Deduplication key collision on insert, safely ignore.
            return null;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Resolve recipient from Project PIC or Role.
     */
    public function resolveRecipients(Project $project, array $roles): array
    {
        $recipients = [];

        // Try to get PIC first
        $pics = $project->assignments()->whereNull('ended_at')->get();
        if ($pics->isNotEmpty()) {
            foreach ($pics as $assignment) {
                $pic = $assignment->user;
                if ($pic && $pic->hasAnyRole($roles)) {
                    $recipients[$pic->id] = $pic;
                }
            }
        }

        // If no PIC found for the given roles, fallback to Role
        if (empty($recipients)) {
            $users = User::role($roles)->where('status', 'ACTIVE')->get();
            foreach ($users as $user) {
                $recipients[$user->id] = $user;
            }
        }

        return array_values($recipients);
    }
}
