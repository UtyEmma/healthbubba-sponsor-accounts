<?php

namespace App\Http\Middleware;

use App\Enums\AccountTypes;
use App\Models\User;
use App\Models\Workspace;
use App\Services\InstitutionalOnboarding\InstitutionalOnboardingService;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsureInstitutionalOnboardingComplete
{
    public function __construct(private InstitutionalOnboardingService $onboarding) {}

    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        if ($request->routeIs('institutional_onboarding.*', 'workspaces.select')) {
            return $next($request);
        }

        $user = $request->user();
        $workspace = Workspace::current();

        if (! $user instanceof User
            || ! $workspace instanceof Workspace
            || $workspace->type !== AccountTypes::INSTITUTION) {
            return $next($request);
        }

        if ($this->onboarding->requiresProfileCompletion($workspace)) {
            return redirect()->route('institutional_onboarding.organization.edit');
        }

        if (! $this->onboarding->hasProfileApproved($workspace)) {
            return redirect()->route('institutional_onboarding.support');
        }

        return $next($request);
    }
}
