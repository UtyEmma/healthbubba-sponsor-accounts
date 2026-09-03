<?php

namespace App\Actions\Auth;

use App\DTOs\Auth\AccountSetupAuthentication;
use App\Enums\AccountTypes;
use App\Enums\WorkspaceMembers\WorkspaceMemberRole;
use App\Enums\WorkspaceMembers\WorkspaceMemberStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class AuthenticateAccountSetupAction
{
    public function execute(string $email, string $password, AccountTypes $accountType): AccountSetupAuthentication
    {
        $user = User::query()->where('email', Str::lower(trim($email)))->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'The provided credentials are incorrect.',
            ]);
        }

        $memberships = $user->workspaceMemberships()
            ->with('workspace')
            ->whereHas('workspace', fn (Builder $query): Builder => $query->where('type', $accountType));

        if ((clone $memberships)->where('role', WorkspaceMemberRole::Owner)->exists()) {
            throw ValidationException::withMessages([
                'account_type' => 'You already own an account of this type. Sign in instead.',
            ]);
        }

        $activeMembership = (clone $memberships)
            ->where('status', WorkspaceMemberStatus::Active)
            ->orderByDesc('last_selected_at')
            ->orderByDesc('id')
            ->first();

        return new AccountSetupAuthentication($user, $activeMembership);
    }
}
