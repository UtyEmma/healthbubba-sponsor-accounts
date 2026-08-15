<?php

namespace App\Http\Controllers\WorkspaceMembers;

use App\Actions\WorkspaceMembers\InviteWorkspaceMemberAction;
use App\Http\Requests\WorkspaceMembers\StoreWorkspaceMemberInvitationRequest;
use Illuminate\Http\RedirectResponse;

final readonly class StoreWorkspaceMemberInvitationController
{
    public function __construct(private InviteWorkspaceMemberAction $invite) {}

    public function __invoke(StoreWorkspaceMemberInvitationRequest $request): RedirectResponse
    {
        $this->invite->execute($request->workspace(), $request->teamUser(), $request->invitationData());

        return back()->with('success', 'Team invitation sent.');
    }
}
