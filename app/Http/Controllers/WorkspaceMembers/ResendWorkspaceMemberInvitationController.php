<?php

namespace App\Http\Controllers\WorkspaceMembers;

use App\Actions\WorkspaceMembers\ResendWorkspaceMemberInvitationAction;
use App\Http\Requests\WorkspaceMembers\ManageWorkspaceMemberRequest;
use Illuminate\Http\RedirectResponse;

final readonly class ResendWorkspaceMemberInvitationController
{
    public function __construct(private ResendWorkspaceMemberInvitationAction $resend) {}

    public function __invoke(ManageWorkspaceMemberRequest $request): RedirectResponse
    {
        $this->resend->execute($request->target());

        return back()->with('success', 'Team invitation resent.');
    }
}
