<?php

namespace App\Http\Controllers\Auth;

use App\Actions\AccountVerification\IssueAccountVerificationChallengeAction;
use App\Http\Requests\Auth\SendAccountVerificationCodeRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

final readonly class SendAccountVerificationCodeController
{
    public function __construct(private IssueAccountVerificationChallengeAction $issueChallenge) {}

    public function __invoke(SendAccountVerificationCodeRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->issueChallenge->execute($user, $request->channel());

        return back();
    }
}
