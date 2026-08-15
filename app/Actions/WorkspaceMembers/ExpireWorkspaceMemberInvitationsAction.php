<?php

namespace App\Actions\WorkspaceMembers;

use App\Enums\WorkspaceMembers\WorkspaceMemberStatus;
use App\Models\Workspace;
use App\Models\WorkspaceMember;

final class ExpireWorkspaceMemberInvitationsAction
{
    public function execute(?Workspace $workspace = null): int
    {
        return WorkspaceMember::query()
            ->when($workspace, fn ($query) => $query->whereBelongsTo($workspace))
            ->where('status', WorkspaceMemberStatus::Invited)
            ->where('expires_at', '<=', now())
            ->update(['status' => WorkspaceMemberStatus::Expired, 'updated_at' => now()]);
    }
}
