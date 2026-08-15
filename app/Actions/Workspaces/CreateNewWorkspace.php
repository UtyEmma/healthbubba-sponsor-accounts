<?php

namespace App\Actions\Workspaces;

use App\Enums\WorkspaceMembers\WorkspaceMemberRole;
use App\Enums\WorkspaceMembers\WorkspaceMemberStatus;
use App\Models\User;
use App\Models\Workspace;
use Exception;
use Illuminate\Support\Str;

final class CreateNewWorkspace
{
    /** @param array<string, mixed> $data */
    public function execute(User $user, array $data): Workspace
    {
        if ($user->workspaceMemberships()->exists()) {
            throw new Exception('This account already belongs to an existing workspace.');
        }

        $workspace = Workspace::query()->create($data);
        $workspace->members()->create([
            'public_id' => (string) Str::ulid(),
            'user_id' => $user->getKey(),
            'name' => $user->name,
            'email' => Str::lower(trim($user->email)),
            'role' => WorkspaceMemberRole::Owner,
            'status' => WorkspaceMemberStatus::Active,
            'accepted_at' => now(),
            'last_selected_at' => now(),
        ]);

        return $workspace;
    }
}
