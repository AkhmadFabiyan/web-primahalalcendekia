<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Documents\Models\Document;
use App\Modules\Projects\Enums\ProjectStatus;
use App\Modules\Projects\Enums\AssignmentRole;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\ProjectAssignment;

class DocumentPolicy
{
    /**
     * View any models.
     */
    public function viewAny(User $user): bool
    {
        // Internal users can see documents
        return $user->client_id === null;
    }

    /**
     * View the model.
     */
    public function view(User $user, Document $document): bool
    {
        if ($user->client_id !== null) {
            // Client checks
            return $document->is_client_visible && $document->project->client_id === $user->client_id;
        }

        return true; // Internals can view
    }

    /**
     * Generic create (not used, we use context actions on relations)
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Generic update
     */
    public function update(User $user, Document $document): bool
    {
        return false; // Contextual replace
    }

    /**
     * Delete
     */
    public function delete(User $user, Document $document): bool
    {
        return false; // Archiving is used
    }

    public function restore(User $user, Document $document): bool
    {
        return false;
    }

    public function forceDelete(User $user, Document $document): bool
    {
        return false;
    }

    // --- Custom domain-specific actions ---

    public function upload(User $user, Project $project): bool
    {
        if ($user->client_id !== null) {
            return false;
        }

        if (in_array($project->status, [
            ProjectStatus::WAITING_ACTIVATION,
            ProjectStatus::CANCELLED,
            ProjectStatus::COMPLETED,
        ])) {
            return false;
        }

        // Super admin can always upload
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        // Assigned Admin can upload
        $isAdminAssigned = ProjectAssignment::where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->where('assignment_role', AssignmentRole::ADMIN->value)
            ->whereNull('ended_at')
            ->exists();

        if ($isAdminAssigned) {
            return true;
        }

        // TODO: Expand logic for Entry / Auditor if they need to upload specific categories
        return false;
    }

    public function archive(User $user, Document $document): bool
    {
        return $this->upload($user, $document->project);
    }
    
    public function replace(User $user, Document $document): bool
    {
        return $this->upload($user, $document->project);
    }
}
