<?php

namespace App\Http\Controllers\WorkspaceMembers;

use App\Actions\WorkspaceMembers\AcceptWorkspaceMemberInvitationAction;
use App\Actions\WorkspaceMembers\SelectWorkspaceAction;
use App\Http\Requests\WorkspaceMembers\AcceptWorkspaceMemberInvitationRequest;
use App\Models\User;
use App\Models\WorkspaceMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

final readonly class AcceptWorkspaceMemberInvitationController
{
    public function __construct(
        private AcceptWorkspaceMemberInvitationAction $accept,
        private SelectWorkspaceAction $select,
    ) {}

    public function __invoke(AcceptWorkspaceMemberInvitationRequest $request, WorkspaceMember $workspaceMember): RedirectResponse
    {
        abort_unless((int) $request->query('version') === $workspaceMember->invitation_version, 403);
        $member = $this->accept->execute($workspaceMember, $request->user(), $request->validated('password'));
        $user = $member->user;

        if ($user instanceof User && ($request->user()?->is($user) === true || $request->filled('password'))) {
            Auth::login($user);
            $request->session()->regenerate();
            $this->select->execute($request, $user, $member->workspace);
        }

        return redirect()->route('home')->with('success', 'Team invitation accepted.');
    }
}
