<?php

namespace App\Http\Controllers\InstitutionalCampaigns;

use App\Actions\WorkspaceBeneficiaries\InviteWorkspaceBeneficiaryAction;
use App\Http\Requests\InstitutionalCampaigns\StoreCampaignBeneficiaryRequest;
use App\Models\Campaign;
use Illuminate\Http\RedirectResponse;

final readonly class StoreCampaignBeneficiaryController
{
    public function __construct(
        private InviteWorkspaceBeneficiaryAction $invite,
    ) {}

    public function __invoke(
        StoreCampaignBeneficiaryRequest $request,
        Campaign $campaign,
    ): RedirectResponse {
        $this->invite->execute(
            $request->workspace(),
            $request->onboardingUser(),
            $request->invitationData(),
            $campaign,
        );

        return to_route('campaigns.show', $campaign)
            ->with('success', 'Beneficiary enrolled successfully.');
    }
}
