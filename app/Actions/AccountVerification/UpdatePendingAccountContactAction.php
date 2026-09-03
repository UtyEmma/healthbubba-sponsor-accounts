<?php

namespace App\Actions\AccountVerification;

use App\Enums\WorkspaceMembers\WorkspaceMemberRole;
use App\Models\AccountVerificationChallenge;
use App\Models\User;
use App\Models\WorkspaceMember;
use Illuminate\Support\Facades\DB;

final readonly class UpdatePendingAccountContactAction
{
    public function execute(User $user, string $email, string $phone): void
    {
        DB::transaction(function () use ($user, $email, $phone): void {
            $lockedUser = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            $lockedUser->forceFill([
                'email' => $email,
                'phone' => $phone,
                'email_verified_at' => null,
                'phone_verified_at' => null,
                'account_verified_at' => null,
            ])->save();

            WorkspaceMember::query()
                ->whereBelongsTo($lockedUser)
                ->where('role', WorkspaceMemberRole::Owner)
                ->update([
                    'email' => $email,
                    'phone' => $phone,
                ]);

            AccountVerificationChallenge::query()
                ->whereBelongsTo($lockedUser)
                ->whereNull('consumed_at')
                ->update(['consumed_at' => now()]);
        });
    }
}
