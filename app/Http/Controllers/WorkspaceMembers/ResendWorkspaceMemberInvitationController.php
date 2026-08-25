<?php

namespace App\Http\Controllers\WorkspaceMembers;

use App\Actions\WorkspaceMembers\ResendWorkspaceMemberInvitationAction;
use App\Http\Requests\WorkspaceMembers\ManageWorkspaceMemberRequest;
use App\Models\WorkspaceMember;
use Illuminate\Http\RedirectResponse;

final readonly class ResendWorkspaceMemberInvitationController
{
    public function __construct(private ResendWorkspaceMemberInvitationAction $resend) {}

    public function __invoke(
        ManageWorkspaceMemberRequest $request,
        WorkspaceMember $workspaceMember,
    ): RedirectResponse {
        $this->resend->execute($workspaceMember);

        return back()->with('success', 'Team invitation resent.');
    }
}
