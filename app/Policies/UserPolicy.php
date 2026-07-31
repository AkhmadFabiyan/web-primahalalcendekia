<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can(\App\Enums\Permission::ViewUsers->value) || $user->can(\App\Enums\Permission::ManageUsers->value);
    }

    public function view(User $user, User $model): bool
    {
        return $user->can(\App\Enums\Permission::ViewUsers->value) || $user->can(\App\Enums\Permission::ManageUsers->value);
    }

    public function create(User $user): bool
    {
        return $user->can(\App\Enums\Permission::ManageUsers->value);
    }

    public function update(User $user, User $model): bool
    {
        return $user->can(\App\Enums\Permission::ManageUsers->value);
    }

    public function delete(User $user, User $model): bool
    {
        return false;
    }

    public function restore(User $user, User $model): bool
    {
        return false;
    }

    public function forceDelete(User $user, User $model): bool
    {
        return false;
    }
}
