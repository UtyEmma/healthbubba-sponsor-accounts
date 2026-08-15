<?php

namespace App\Http\Controllers\WorkspaceMembers;

use App\Actions\WorkspaceMembers\ExpireWorkspaceMemberInvitationsAction;
use App\Http\Resources\WorkspaceMemberInvitationResource;
use App\Models\User;
use App\Models\WorkspaceMember;
use App\Services\WorkspaceMembers\WorkspaceMemberInvitationUrlService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ShowWorkspaceMemberInvitationController
{
    public function __construct(
        private ExpireWorkspaceMemberInvitationsAction $expire,
        private WorkspaceMemberInvitationUrlService $urls,
    ) {}

    public function __invoke(Request $request, WorkspaceMember $workspaceMember): Response
    {
        abort_unless(URL::hasCorrectSignature($request), 403);
        abort_unless((int) $request->query('version') === $workspaceMember->invitation_version, 403);
        $workspaceMember->loadMissing('workspace');

        if ($workspaceMember->hasExpired()) {
            $this->expire->execute($workspaceMember->workspace);
            $workspaceMember->refresh();
        } elseif ($workspaceMember->isInvited()) {
            abort_unless($request->hasValidSignature(), 403);
        }

        $existingUser = User::query()->where('email', $workspaceMember->email)->first();
        $workspaceMember->setRelation('matchedUser', $existingUser);
        $authenticatedUser = $request->user();
        $canAccept = $workspaceMember->isInvited()
            && ($existingUser === null || ($authenticatedUser instanceof User && $authenticatedUser->is($existingUser)));

        if ($existingUser !== null && ! $authenticatedUser instanceof User) {
            $request->session()->put('url.intended', $request->fullUrl());
        }

        return Inertia::render('invitations/workspace-team', [
            'invitation' => new WorkspaceMemberInvitationResource($workspaceMember),
            'acceptUrl' => $canAccept ? $this->urls->accept($workspaceMember) : null,
            'declineUrl' => $workspaceMember->isInvited() ? $this->urls->decline($workspaceMember) : null,
        ]);
    }
}
