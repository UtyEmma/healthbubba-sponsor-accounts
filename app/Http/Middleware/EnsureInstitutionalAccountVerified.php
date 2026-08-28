<?php

namespace App\Http\Middleware;

use App\Enums\AccountTypes;
use App\Models\User;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureInstitutionalAccountVerified
{
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user instanceof User
            && $user->type === AccountTypes::INSTITUTION
            && ! $user->hasVerifiedAccount()) {
            return redirect()->route('account_verification.show');
        }

        return $next($request);
    }
}
