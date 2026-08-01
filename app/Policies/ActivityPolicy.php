<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Logs\Models\Activity;

class ActivityPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('logs.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Activity $activity): bool
    {
        if (!$user->can('logs.view')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        // Klien only sees their own activity that is marked as client visible
        if ($user->isKlien()) {
            if (!$activity->is_client_visible) {
                return false;
            }
            // Check project ownership
            return $activity->project_id && $activity->project && $activity->project->client_id === $user->client_id;
        }

        // Internal user with permission
        return true;
    }

    public function create(User $user): bool { return false; }
    public function update(User $user, Activity $activity): bool { return false; }
    public function delete(User $user, Activity $activity): bool { return false; }
    public function restore(User $user, Activity $activity): bool { return false; }
    public function forceDelete(User $user, Activity $activity): bool { return false; }
}
