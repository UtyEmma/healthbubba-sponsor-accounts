<?php

namespace App\Actions\Auth;

use App\Actions\Workspaces\CreateNewWorkspace;
use App\DTOs\Auth\StoreOwnedWorkspaceData;
use App\Models\User;
use App\Models\Workspace;

final readonly class StoreOwnedWorkspaceAction
{
    public function __construct(private CreateNewWorkspace $createWorkspace) {}

    public function execute(User $user, StoreOwnedWorkspaceData $data): Workspace
    {
        return $this->createWorkspace->execute(
            $user,
            $data->workspaceData($user->name, $user->phone),
        );
    }
}
