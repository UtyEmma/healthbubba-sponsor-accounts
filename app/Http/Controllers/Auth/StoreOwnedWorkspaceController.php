<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\StoreOwnedWorkspaceAction;
use App\Http\Requests\Auth\StoreOwnedWorkspaceRequest;
use App\Models\User;
use Illuminate\Http\Response;

final readonly class StoreOwnedWorkspaceController
{
    public function __construct(private StoreOwnedWorkspaceAction $storeWorkspace) {}

    public function __invoke(StoreOwnedWorkspaceRequest $request): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $workspace = $this->storeWorkspace->execute($user, $request->workspaceData());
        $request->session()->put('current_workspace_id', $workspace->getKey());
        $request->session()->forget(StoreOwnedWorkspaceRequest::SESSION_KEY);

        return response()->noContent();
    }
}
