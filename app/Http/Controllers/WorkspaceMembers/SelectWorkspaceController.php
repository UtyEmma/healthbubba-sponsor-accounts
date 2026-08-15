<?php

namespace App\Http\Controllers\WorkspaceMembers;

use App\Actions\WorkspaceMembers\SelectWorkspaceAction;
use App\Http\Requests\WorkspaceMembers\SelectWorkspaceRequest;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;

final readonly class SelectWorkspaceController
{
    public function __construct(private SelectWorkspaceAction $select) {}

    public function __invoke(SelectWorkspaceRequest $request, Workspace $workspace): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $this->select->execute($request, $user, $workspace);

        return redirect()->route('home');
    }
}
