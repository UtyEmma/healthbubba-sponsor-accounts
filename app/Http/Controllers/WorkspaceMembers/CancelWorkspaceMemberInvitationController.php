<?php

namespace App\Http\Controllers\WorkspaceMembers;

use App\Actions\WorkspaceMembers\CancelWorkspaceMemberInvitationAction;
use App\Http\Requests\WorkspaceMembers\ManageWorkspaceMemberRequest;
use Illuminate\Http\RedirectResponse;

final readonly class CancelWorkspaceMemberInvitationController
{
    public function __construct(private CancelWorkspaceMemberInvitationAction $cancel) {}

    public function __invoke(ManageWorkspaceMemberRequest $request): RedirectResponse
    {
        $this->cancel->execute($request->target());

        return back()->with('success', 'Team invitation cancelled.');
    }
}
