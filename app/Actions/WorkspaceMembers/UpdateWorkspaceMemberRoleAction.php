<?php

namespace App\Actions\WorkspaceMembers;

use App\Enums\WorkspaceMembers\WorkspaceMemberRole;
use App\Enums\WorkspaceMembers\WorkspaceMemberStatus;
use App\Models\WorkspaceMember;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class UpdateWorkspaceMemberRoleAction
{
    public function execute(WorkspaceMember $target, WorkspaceMemberRole $role): WorkspaceMember
    {
        return DB::transaction(function () use ($target, $role): WorkspaceMember {
            $member = WorkspaceMember::query()->whereKey($target->getKey())->lockForUpdate()->firstOrFail();

            if ($member->isOwner() || $role === WorkspaceMemberRole::Owner) {
                throw ValidationException::withMessages(['role' => 'Workspace ownership cannot be changed.']);
            }

            if (! in_array($member->status, [WorkspaceMemberStatus::Active, WorkspaceMemberStatus::Disabled], true)) {
                throw ValidationException::withMessages(['role' => 'Only accepted team members can have their role changed.']);
            }

            if ($member->role !== $role) {
                $member->update(['role' => $role]);
            }

            return $member;
        });
    }
}
