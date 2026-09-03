<?php

namespace App\Actions\Auth;

use App\DTOs\Auth\AuthenticatedWorkspaceLogin;
use App\Enums\AccountTypes;
use App\Enums\WorkspaceMembers\WorkspaceMemberStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class AuthenticateWorkspaceLoginAction
{
    public function execute(string $email, string $password, AccountTypes $accountType): ?AuthenticatedWorkspaceLogin
    {
        $user = User::query()->where('email', Str::lower(trim($email)))->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            return null;
        }

        $membership = $user->workspaceMemberships()
            ->with('workspace')
            ->where('status', WorkspaceMemberStatus::Active)
            ->whereHas('workspace', fn (Builder $query): Builder => $query->where('type', $accountType))
            ->orderByDesc('last_selected_at')
            ->orderByDesc('id')
            ->first();

        if ($membership === null) {
            throw ValidationException::withMessages([
                'account_type' => 'Your credentials are correct, but you do not have active access to this account type. Set up a new account to continue.',
            ]);
        }

        $membership->update(['last_selected_at' => now()]);

        return new AuthenticatedWorkspaceLogin($user, $membership);
    }
}
