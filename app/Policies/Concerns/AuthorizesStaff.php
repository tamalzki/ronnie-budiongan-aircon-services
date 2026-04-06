<?php

namespace App\Policies\Concerns;

use App\Models\User;

/**
 * All authenticated users may manage operational data.
 * Centralizes policy logic so a future role column or Gate::before can override in one place.
 */
trait AuthorizesStaff
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, $model): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, $model): bool
    {
        return true;
    }

    public function delete(User $user, $model): bool
    {
        return true;
    }
}
