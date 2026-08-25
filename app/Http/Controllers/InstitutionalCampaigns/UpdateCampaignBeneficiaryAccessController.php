<?php

namespace App\Http\Controllers\InstitutionalCampaigns;

use App\Actions\WorkspaceBeneficiaries\UpdateWorkspaceBeneficiaryAccessAction;
use App\Enums\WorkspaceBeneficiaries\WorkspaceBeneficiaryAccessAction;
use App\Http\Requests\InstitutionalCampaigns\UpdateCampaignBeneficiaryAccessRequest;
use App\Models\Campaign;
use App\Models\WorkspaceBeneficiary;
use Illuminate\Http\RedirectResponse;

final readonly class UpdateCampaignBeneficiaryAccessController
{
    public function __construct(
        private UpdateWorkspaceBeneficiaryAccessAction $updateAccess,
    ) {}

    public function __invoke(
        UpdateCampaignBeneficiaryAccessRequest $request,
        Campaign $campaign,
        WorkspaceBeneficiary $workspaceBeneficiary,
    ): RedirectResponse {
        $action = $request->accessAction();
        $this->updateAccess->execute(
            $request->workspace(),
            $request->onboardingUser(),
            $request->beneficiary(),
            $action,
        );

        return back()->with('success', match ($action) {
            WorkspaceBeneficiaryAccessAction::Suspend => 'Beneficiary access suspended successfully.',
            WorkspaceBeneficiaryAccessAction::Restore => 'Beneficiary access restored successfully.',
            WorkspaceBeneficiaryAccessAction::Revoke => 'Beneficiary removed from the campaign successfully.',
        });
    }
}
