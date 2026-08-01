<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Projects\Models\ProjectArchive;

class ProjectArchivePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('archives.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ProjectArchive $archive): bool
    {
        if (!$user->can('archives.view')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        // Klien or Admin Perusahaan can only see their own archives
        if ($user->isKlien() || $user->isAdminPerusahaan()) {
            return $user->client_id === $archive->project->client_id;
        }

        // Other internal staff with permission can view
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ProjectArchive $projectArchive): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ProjectArchive $projectArchive): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ProjectArchive $projectArchive): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ProjectArchive $projectArchive): bool
    {
        return false;
    }
}
