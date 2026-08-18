<?php

namespace App\Http\Controllers\InstitutionalOnboarding;

use App\Http\Requests\InstitutionalOnboarding\ShowInstitutionalSupportRequest;
use App\Http\Resources\CampaignResource;
use App\Http\Resources\WorkspaceResource;
use App\Models\Campaign;
use App\Services\InstitutionalOnboarding\InstitutionalOnboardingService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ShowInstitutionalSupportController
{
    public function __construct(private InstitutionalOnboardingService $onboarding) {}

    public function __invoke(ShowInstitutionalSupportRequest $request): Response|RedirectResponse
    {
        $workspace = $request->workspace();

        if ($this->onboarding->requiresProfileCompletion($workspace)) {
            return redirect()->route('institutional_onboarding.organization.edit');
        }

        if ($this->onboarding->hasProfileApproved($workspace)) {
            return redirect()->route('home');
        }

        $workspace->load('latestCampaign');
        $campaign = $workspace->latestCampaign;

        $supportEmail = config('support.email');
        abort_unless(is_string($supportEmail) && $supportEmail !== '', 500, 'Support email is not configured.');

        $query = http_build_query([
            'subject' => "Institutional sponsor onboarding: {$workspace->name}",
            'body' => "Hello HealthBubba Support,\n\nPlease assist with subscription setup for {$workspace->name} (Workspace #{$workspace->getKey()}) and its campaign".($campaign instanceof Campaign ? " {$campaign->name}." : '.'),
        ], '', '&', PHP_QUERY_RFC3986);

        return Inertia::render('institutional-onboarding/contact-support', [
            'organization' => new WorkspaceResource($workspace),
            'campaign' => $campaign instanceof Campaign ? new CampaignResource($campaign) : null,
            'supportEmail' => $supportEmail,
            'supportMailtoUrl' => "mailto:{$supportEmail}?{$query}",
        ]);
    }
}
