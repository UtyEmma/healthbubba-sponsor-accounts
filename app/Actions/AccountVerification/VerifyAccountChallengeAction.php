<?php

namespace App\Actions\AccountVerification;

use App\Enums\VerificationChannel;
use App\Models\AccountVerificationChallenge;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final readonly class VerifyAccountChallengeAction
{
    public function execute(User $user, VerificationChannel $channel, string $code): void
    {
        DB::transaction(function () use ($user, $channel, $code): void {
            $challenge = AccountVerificationChallenge::query()
                ->whereBelongsTo($user)
                ->where('channel', $channel)
                ->whereNull('consumed_at')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $challenge instanceof AccountVerificationChallenge || $challenge->expires_at->isPast()) {
                $challenge?->update(['consumed_at' => now()]);

                throw ValidationException::withMessages([
                    'code' => 'This code has expired. Request a new one.',
                ]);
            }

            if ($challenge->attempts >= 5) {
                $challenge->update(['consumed_at' => now()]);

                throw ValidationException::withMessages([
                    'code' => 'Too many incorrect attempts. Request a new code.',
                ]);
            }

            if (! Hash::check($code, $challenge->code_hash)) {
                $challenge->increment('attempts');

                throw ValidationException::withMessages([
                    'code' => 'The verification code is incorrect.',
                ]);
            }

            $lockedUser = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            $verifiedAt = now();

            $lockedUser->forceFill([
                'account_verified_at' => $verifiedAt,
                $channel === VerificationChannel::Email ? 'email_verified_at' : 'phone_verified_at' => $verifiedAt,
            ])->save();

            $challenge->update(['consumed_at' => $verifiedAt]);
        });
    }
}
