<?php

namespace App\Http\Controllers\InstitutionalOnboarding;

use App\Http\Requests\InstitutionalOnboarding\ShowInstitutionalOrganizationRequest;
use App\Http\Resources\WorkspaceResource;
use App\Services\InstitutionalOnboarding\InstitutionalOnboardingService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ShowInstitutionalOrganizationController
{
    public function __construct(private InstitutionalOnboardingService $onboarding) {}

    public function __invoke(ShowInstitutionalOrganizationRequest $request): Response|RedirectResponse
    {
        $workspace = $request->workspace();

        if (! $this->onboarding->requiresProfileCompletion($workspace)) {
            return redirect()->route('home');
        }

        return Inertia::render('institutional-onboarding/organization', [
            'organization' => new WorkspaceResource($workspace),
        ]);
    }
}
