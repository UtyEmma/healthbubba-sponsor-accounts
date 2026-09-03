<?php

namespace App\Actions\Auth;

use App\DTOs\Auth\AccountAvailability;
use App\Enums\AccountTypes;
use App\Enums\Auth\AccountAvailabilityStatus;
use App\Enums\WorkspaceMembers\WorkspaceMemberRole;
use App\Enums\WorkspaceMembers\WorkspaceMemberStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final class CheckAccountAvailabilityAction
{
    public function execute(string $email, AccountTypes $accountType): AccountAvailability
    {
        $user = User::query()
            ->where('email', Str::lower(trim($email)))
            ->first();

        if ($user === null) {
            return new AccountAvailability(AccountAvailabilityStatus::NewIdentity, false, true);
        }

        $memberships = $user->workspaceMemberships()
            ->whereHas('workspace', fn (Builder $query): Builder => $query->where('type', $accountType));

        $ownsWorkspace = (clone $memberships)
            ->where('role', WorkspaceMemberRole::Owner)
            ->exists();

        if ($ownsWorkspace) {
            $hasActiveWorkspace = (clone $memberships)
                ->where('status', WorkspaceMemberStatus::Active)
                ->exists();

            return new AccountAvailability(
                AccountAvailabilityStatus::OwnedWorkspace,
                $hasActiveWorkspace,
                false,
            );
        }

        $hasActiveMembership = (clone $memberships)
            ->where('status', WorkspaceMemberStatus::Active)
            ->exists();

        if ($hasActiveMembership) {
            return new AccountAvailability(AccountAvailabilityStatus::MemberWorkspace, true, true);
        }

        return new AccountAvailability(AccountAvailabilityStatus::ExistingIdentity, false, true);
    }
}
