<?php

namespace App\Http\Controllers\WorkspaceMembers;

use App\Actions\WorkspaceMembers\UpdateWorkspaceMemberRoleAction;
use App\Enums\WorkspaceMembers\WorkspaceMemberRole;
use App\Http\Requests\WorkspaceMembers\UpdateWorkspaceMemberRoleRequest;
use App\Models\WorkspaceMember;
use Illuminate\Http\RedirectResponse;

final readonly class UpdateWorkspaceMemberRoleController
{
    public function __construct(private UpdateWorkspaceMemberRoleAction $updateRole) {}

    public function __invoke(
        UpdateWorkspaceMemberRoleRequest $request,
        WorkspaceMember $workspaceMember,
    ): RedirectResponse {
        $this->updateRole->execute($workspaceMember, WorkspaceMemberRole::from($request->validated('role')));

        return back()->with('success', 'Team member role updated.');
    }
}
