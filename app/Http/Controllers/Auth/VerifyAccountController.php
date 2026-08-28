<?php

namespace App\Http\Controllers\Auth;

use App\Actions\AccountVerification\VerifyAccountChallengeAction;
use App\Http\Requests\Auth\VerifyAccountRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

final readonly class VerifyAccountController
{
    public function __construct(private VerifyAccountChallengeAction $verifyChallenge) {}

    public function __invoke(VerifyAccountRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->verifyChallenge->execute(
            user: $user,
            channel: $request->channel(),
            code: (string) $request->validated('code'),
        );

        return redirect()->route('account_verification.completed');
    }
}
