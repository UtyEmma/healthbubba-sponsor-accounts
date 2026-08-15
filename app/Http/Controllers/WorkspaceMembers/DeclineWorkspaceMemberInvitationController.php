<?php

namespace App\Http\Controllers\WorkspaceMembers;

use App\Actions\WorkspaceMembers\DeclineWorkspaceMemberInvitationAction;
use App\Models\WorkspaceMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final readonly class DeclineWorkspaceMemberInvitationController
{
    public function __construct(private DeclineWorkspaceMemberInvitationAction $decline) {}

    public function __invoke(Request $request, WorkspaceMember $workspaceMember): RedirectResponse
    {
        abort_unless((int) $request->query('version') === $workspaceMember->invitation_version, 403);
        $this->decline->execute($workspaceMember);

        return redirect()->to($request->headers->get('referer', '/'));
    }
}
