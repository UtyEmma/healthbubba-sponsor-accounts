<?php

namespace App\Http\Controllers\InstitutionalOnboarding;

use App\Actions\InstitutionalOnboarding\CompleteInstitutionalOrganizationProfileAction;
use App\Http\Requests\InstitutionalOnboarding\CompleteInstitutionalOrganizationProfileRequest;
use Illuminate\Http\RedirectResponse;

final readonly class CompleteInstitutionalOrganizationProfileController
{
    public function __construct(private CompleteInstitutionalOrganizationProfileAction $completeProfile) {}

    public function __invoke(CompleteInstitutionalOrganizationProfileRequest $request): RedirectResponse
    {
        $this->completeProfile->execute(
            workspace: $request->workspace(),
            owner: $request->onboardingUser(),
            data: $request->onboardingData(),
        );

        return redirect()->route('institutional_onboarding.support')
            ->with('success', 'Your campaign details have been submitted to support.');
    }
}
