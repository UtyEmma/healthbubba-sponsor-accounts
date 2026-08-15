<?php

namespace App\Actions\WorkspaceMembers;

use App\Enums\WorkspaceMembers\WorkspaceMemberStatus;
use App\Models\WorkspaceMember;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class UpdateWorkspaceMemberAccessAction
{
    public function execute(WorkspaceMember $target, bool $enabled): WorkspaceMember
    {
        return DB::transaction(function () use ($target, $enabled): WorkspaceMember {
            $member = WorkspaceMember::query()->whereKey($target->getKey())->lockForUpdate()->firstOrFail();

            if ($member->isOwner()) {
                throw ValidationException::withMessages(['member' => 'The workspace owner cannot be disabled.']);
            }

            $required = $enabled ? WorkspaceMemberStatus::Disabled : WorkspaceMemberStatus::Active;
            if ($member->status !== $required) {
                return $member;
            }

            $member->update([
                'status' => $enabled ? WorkspaceMemberStatus::Active : WorkspaceMemberStatus::Disabled,
                'disabled_at' => $enabled ? null : now(),
            ]);

            return $member;
        });
    }
}
