<?php

namespace App\Actions\Auth;

use App\Actions\Marketing\AttributeSignupReferrerAction;
use App\Actions\Workspaces\CreateNewWorkspace;
use App\DTOs\Auth\StoreOwnedWorkspaceData;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

final readonly class StoreOwnedWorkspaceAction
{
    public function __construct(private CreateNewWorkspace $createWorkspace, private AttributeSignupReferrerAction $attributeReferrer) {}

    public function execute(User $user, StoreOwnedWorkspaceData $data): Workspace
    {
        return DB::transaction(function () use ($user, $data): Workspace {
            $workspace = $this->createWorkspace->execute(
                $user,
                $data->workspaceData($user->name, $user->phone),
            );
            $this->attributeReferrer->execute($user, $data->referralCode);

            return $workspace;
        });
    }
}
