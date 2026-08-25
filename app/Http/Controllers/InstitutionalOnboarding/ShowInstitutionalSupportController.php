<?php

namespace App\Http\Controllers\InstitutionalOnboarding;

use App\Http\Requests\InstitutionalOnboarding\ShowInstitutionalSupportRequest;
use App\Services\InstitutionalOnboarding\InstitutionalOnboardingService;
use Illuminate\Http\RedirectResponse;

final readonly class ShowInstitutionalSupportController
{
    public function __construct(private InstitutionalOnboardingService $onboarding) {}

    public function __invoke(ShowInstitutionalSupportRequest $request): RedirectResponse
    {
        $workspace = $request->workspace();

        if ($this->onboarding->requiresProfileCompletion($workspace)) {
            return redirect()->route('institutional_onboarding.organization.edit');
        }

        return redirect()->route('home');
    }
}
