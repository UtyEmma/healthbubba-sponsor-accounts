<?php

namespace App\Actions\AccountVerification;

use App\Enums\VerificationChannel;
use App\Models\AccountVerificationChallenge;
use App\Models\User;
use App\Notifications\AccountVerificationCodeNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class IssueAccountVerificationChallengeAction
{
    public function execute(User $user, VerificationChannel $channel): AccountVerificationChallenge
    {
        $code = Str::padLeft((string) random_int(0, 999999), 6, '0');

        $challenge = DB::transaction(function () use ($user, $channel, $code): AccountVerificationChallenge {
            $lockedUser = User::query()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $latestChallenge = AccountVerificationChallenge::query()
                ->whereBelongsTo($lockedUser)
                ->where('channel', $channel)
                ->whereNull('consumed_at')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($latestChallenge instanceof AccountVerificationChallenge
                && $latestChallenge->sent_at->addSeconds(30)->isFuture()) {
                throw ValidationException::withMessages([
                    'channel' => 'Please wait before requesting another code.',
                ]);
            }

            AccountVerificationChallenge::query()
                ->whereBelongsTo($lockedUser)
                ->whereNull('consumed_at')
                ->update(['consumed_at' => now()]);

            $destination = $channel === VerificationChannel::Email
                ? $lockedUser->email
                : $lockedUser->phone;

            if (! is_string($destination) || $destination === '') {
                throw ValidationException::withMessages([
                    'channel' => 'The selected verification contact is unavailable.',
                ]);
            }

            return $lockedUser->verificationChallenges()->create([
                'channel' => $channel,
                'destination' => $destination,
                'code_hash' => Hash::make($code),
                'attempts' => 0,
                'sent_at' => now(),
                'expires_at' => now()->addMinutes(10),
            ]);
        });

        $user->notify(new AccountVerificationCodeNotification(
            challengeId: (int) $challenge->getKey(),
            channel: $channel,
            code: $code,
        ));

        return $challenge;
    }
}
