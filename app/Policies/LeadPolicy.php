<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Leads\Models\Lead;
use Illuminate\Auth\Access\Response;

class LeadPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(\App\Enums\Permission::ViewLeads->value);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Lead $lead): bool
    {
        if (! $user->hasPermissionTo(\App\Enums\Permission::ViewLeads->value)) {
            return false;
        }

        if ($user->hasRole(\App\Enums\Role::MARKETING->value)) {
            return $lead->marketing_id === $user->id;
        }

        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo(\App\Enums\Permission::CreateLeads->value);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Lead $lead): bool
    {
        if (! $user->hasPermissionTo(\App\Enums\Permission::UpdateLeads->value)) {
            return false;
        }

        // Lead can only be edited if DRAFT
        if ($lead->status !== \App\Modules\Leads\Enums\LeadStatus::DRAFT) {
            return false;
        }

        if ($user->hasRole(\App\Enums\Role::MARKETING->value)) {
            return $lead->marketing_id === $user->id;
        }

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Lead $lead): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Lead $lead): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Lead $lead): bool
    {
        return false;
    }
}
