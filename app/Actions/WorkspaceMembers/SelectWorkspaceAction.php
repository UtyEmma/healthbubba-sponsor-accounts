<?php

namespace App\Actions\WorkspaceMembers;

use App\Enums\WorkspaceMembers\WorkspaceMemberStatus;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class SelectWorkspaceAction
{
    public function execute(Request $request, User $user, Workspace $workspace): void
    {
        $member = WorkspaceMember::query()
            ->whereBelongsTo($workspace)
            ->whereBelongsTo($user)
            ->where('status', WorkspaceMemberStatus::Active)
            ->first();

        if ($member === null) {
            throw ValidationException::withMessages(['workspace' => 'You do not have active access to this workspace.']);
        }

        $member->update(['last_selected_at' => now()]);
        $request->session()->put('current_workspace_id', $workspace->getKey());
    }
}
