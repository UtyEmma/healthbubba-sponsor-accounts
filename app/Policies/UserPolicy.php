<?php

namespace App\Policies;

use App\Enums\Account\Roles;
use App\Models\User;

final class UserPolicy
{
    public function viewAny(User $administrator): bool
    {
        return $administrator->isAdmin();
    }

    public function view(User $administrator, User $user): bool
    {
        return $administrator->isAdmin();
    }

    public function create(User $administrator): bool
    {
        return $administrator->isAdmin();
    }

    public function update(User $administrator, User $user): bool
    {
        return $administrator->isSuperAdmin() || $user->role === Roles::USER;
    }

    public function delete(User $administrator, User $user): bool
    {
        return false;
    }

    public function deleteAny(User $administrator): bool
    {
        return false;
    }
}
