<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AccountTypes;
use App\Models\AccountVerificationChallenge;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ShowAccountVerificationController
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        abort_unless($user instanceof User && $user->type === AccountTypes::INSTITUTION, 404);

        if ($user->hasVerifiedAccount()) {
            return redirect()->route('account_verification.completed');
        }

        $challenge = $user->verificationChallenges()
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if ($challenge instanceof AccountVerificationChallenge && ! $challenge->isActive()) {
            $challenge = null;
        }

        return Inertia::render('auth/institutional-verification', [
            'verification' => [
                'email' => $user->email,
                'phone' => $user->phone,
                'smsAvailable' => (bool) config('services.termii.configured'),
                'challenge' => $challenge instanceof AccountVerificationChallenge
                    ? [
                        'channel' => $challenge->channel->value,
                        'destination' => $challenge->destination,
                        'resendAt' => $challenge->sent_at->addSeconds(30)->toISOString(),
                        'expiresAt' => $challenge->expires_at->toISOString(),
                    ]
                    : null,
            ],
        ]);
    }
}
