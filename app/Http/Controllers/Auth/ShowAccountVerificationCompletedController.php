<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AccountTypes;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ShowAccountVerificationCompletedController
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        abort_unless($user instanceof User && $user->type === AccountTypes::INSTITUTION, 404);

        if (! $user->hasVerifiedAccount()) {
            return redirect()->route('account_verification.show');
        }

        return Inertia::render('auth/institutional-registration-completed');
    }
}
