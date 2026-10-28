<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace;

final class WorkspacePolicy
{
    public function viewAny(User $administrator): bool
    {
        return $administrator->isAdmin();
    }

    public function view(User $administrator, Workspace $workspace): bool
    {
        return $administrator->isAdmin();
    }

    public function update(User $administrator, Workspace $workspace): bool
    {
        return $administrator->isAdmin();
    }

    public function delete(User $administrator, Workspace $workspace): bool
    {
        return false;
    }

    public function deleteAny(User $administrator): bool
    {
        return false;
    }
}
