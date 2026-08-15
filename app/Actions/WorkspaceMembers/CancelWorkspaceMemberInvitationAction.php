<?php

namespace App\Actions\WorkspaceMembers;

use App\Enums\WorkspaceMembers\WorkspaceMemberStatus;
use App\Models\WorkspaceMember;
use Illuminate\Support\Facades\DB;

final class CancelWorkspaceMemberInvitationAction
{
    public function execute(WorkspaceMember $invitation): WorkspaceMember
    {
        return DB::transaction(function () use ($invitation): WorkspaceMember {
            $member = WorkspaceMember::query()->whereKey($invitation->getKey())->lockForUpdate()->firstOrFail();

            if ($member->isInvited()) {
                $member->update(['status' => WorkspaceMemberStatus::Cancelled, 'cancelled_at' => now()]);
            }

            return $member;
        });
    }
}
