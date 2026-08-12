<?php

namespace App\Services\Activity;

use App\Enums\Account\Roles;
use App\Enums\Account\Status;
use App\Enums\AccountTypes;
use App\Models\User;
use App\Models\Workspace;

final class WorkspaceActivityAuthorizationService
{
    public function canView(User $user, Workspace $workspace): bool
    {
        if ($user->status !== Status::ACTIVE
            || ! in_array($workspace->type, [AccountTypes::INDIVIDUAL, AccountTypes::BUSINESS], true)) {
            return false;
        }

        if ($user->role === Roles::SUPER_ADMIN) {
            return true;
        }

        return $user->workspaces()
            ->whereKey($workspace->getKey())
            ->wherePivot('status', Status::ACTIVE->value)
            ->wherePivotIn('role', [Roles::ADMIN->value, Roles::SUPER_ADMIN->value])
            ->exists();
    }
}
