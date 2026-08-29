<?php

namespace App\Http\Controllers\Institutional;

use App\Actions\WorkspaceBeneficiaries\InviteWorkspaceBeneficiaryAction;
use App\Http\Requests\Institutional\ManageInstitutionalBeneficiaryRequest;
use Illuminate\Http\RedirectResponse;

final readonly class StoreInstitutionalBeneficiaryController
{
    public function __construct(private InviteWorkspaceBeneficiaryAction $invite) {}

    public function __invoke(ManageInstitutionalBeneficiaryRequest $request): RedirectResponse
    {
        $this->invite->execute(
            $request->workspace(),
            $request->onboardingUser(),
            $request->invitationData(),
            $request->campaign(),
        );

        return back()->with('success', 'Beneficiary enrolled successfully.');
    }
}
