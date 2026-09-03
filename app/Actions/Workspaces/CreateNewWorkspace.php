<?php

namespace App\Actions\Workspaces;

use App\DTOs\Workspaces\CreateWorkspaceData;
use App\Enums\AccountTypes;
use App\Enums\WorkspaceMembers\WorkspaceMemberRole;
use App\Enums\WorkspaceMembers\WorkspaceMemberStatus;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class CreateNewWorkspace
{
    public function execute(User $user, CreateWorkspaceData $data): Workspace
    {
        return DB::transaction(function () use ($user, $data): Workspace {
            $lockedUser = User::query()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $alreadyOwnsType = $lockedUser->workspaceMemberships()
                ->where('role', WorkspaceMemberRole::Owner)
                ->whereHas('workspace', fn (Builder $query): Builder => $query->where('type', $data->accountType))
                ->exists();

            if ($alreadyOwnsType) {
                throw ValidationException::withMessages([
                    'account_type' => 'You already own an account of this type.',
                ]);
            }

            $workspace = Workspace::query()->create($data->workspaceAttributes());
            $workspace->members()->create([
                'public_id' => (string) Str::ulid(),
                'user_id' => $lockedUser->getKey(),
                'name' => $lockedUser->name,
                'email' => Str::lower(trim($lockedUser->email)),
                'phone' => $data->memberPhone,
                'job_title' => $data->memberJobTitle,
                'authorization_confirmed_at' => $data->authorizationConfirmed ? now() : null,
                'role' => WorkspaceMemberRole::Owner,
                'status' => WorkspaceMemberStatus::Active,
                'accepted_at' => now(),
                'last_selected_at' => now(),
            ]);

            if ($data->accountType === AccountTypes::INSTITUTION) {
                $startsOn = now()->startOfDay();
                $workspace->fundingProgram()->create([
                    'name' => 'Community Health Program '.$startsOn->year,
                    'starts_on' => $startsOn->toDateString(),
                    'ends_on' => $startsOn->copy()->addYearNoOverflow()->toDateString(),
                ]);
            }

            return $workspace;
        });
    }
}
