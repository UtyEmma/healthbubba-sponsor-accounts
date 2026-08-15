<?php

namespace App\Services\WorkspaceMembers;

use App\Enums\Account\Roles;
use App\Enums\Account\Status;
use App\Enums\WorkspaceMembers\WorkspaceMemberRole;
use App\Enums\WorkspaceMembers\WorkspaceMemberStatus;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;

final class WorkspaceMemberAccessService
{
    public function membership(User $user, Workspace $workspace): ?WorkspaceMember
    {
        return WorkspaceMember::query()
            ->whereBelongsTo($workspace)
            ->whereBelongsTo($user)
            ->first();
    }

    public function canView(User $user, Workspace $workspace): bool
    {
        if ($user->status !== Status::ACTIVE) {
            return false;
        }

        if ($user->role === Roles::SUPER_ADMIN) {
            return true;
        }

        return $this->membership($user, $workspace)?->status === WorkspaceMemberStatus::Active;
    }

    public function canManage(User $user, Workspace $workspace): bool
    {
        if ($user->role === Roles::SUPER_ADMIN) {
            return true;
        }

        $membership = $this->membership($user, $workspace);

        return $membership?->status === WorkspaceMemberStatus::Active
            && in_array($membership->role, [WorkspaceMemberRole::Owner, WorkspaceMemberRole::Administrator], true);
    }

    public function canManageMember(User $user, Workspace $workspace, WorkspaceMember $member): bool
    {
        if (! $this->canManage($user, $workspace)
            || $member->workspace_id !== $workspace->getKey()
            || $member->isOwner()) {
            return false;
        }

        return $member->user_id !== $user->getKey();
    }
}
