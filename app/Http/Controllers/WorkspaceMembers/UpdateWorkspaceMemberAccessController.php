<?php

namespace App\Http\Controllers\WorkspaceMembers;

use App\Actions\WorkspaceMembers\UpdateWorkspaceMemberAccessAction;
use App\Http\Requests\WorkspaceMembers\UpdateWorkspaceMemberAccessRequest;
use App\Models\WorkspaceMember;
use Illuminate\Http\RedirectResponse;

final readonly class UpdateWorkspaceMemberAccessController
{
    public function __construct(private UpdateWorkspaceMemberAccessAction $updateAccess) {}

    public function __invoke(
        UpdateWorkspaceMemberAccessRequest $request,
        WorkspaceMember $workspaceMember,
    ): RedirectResponse {
        $this->updateAccess->execute($workspaceMember, $request->boolean('enabled'));

        return back()->with('success', 'Team member access updated.');
    }
}
