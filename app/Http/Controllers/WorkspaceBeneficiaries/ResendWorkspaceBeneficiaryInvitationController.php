<?php

namespace App\Http\Controllers\WorkspaceBeneficiaries;

use App\Actions\WorkspaceBeneficiaries\ResendWorkspaceBeneficiaryInvitationAction;
use App\Http\Requests\WorkspaceBeneficiaries\ManageWorkspaceBeneficiaryRequest;
use App\Models\WorkspaceBeneficiary;
use Illuminate\Http\RedirectResponse;

final readonly class ResendWorkspaceBeneficiaryInvitationController
{
    public function __construct(private ResendWorkspaceBeneficiaryInvitationAction $resend) {}

    public function __invoke(WorkspaceBeneficiary $workspaceBeneficiary, ManageWorkspaceBeneficiaryRequest $request): RedirectResponse
    {
        $this->resend->execute($request->workspace(), $request->user(), $request->invitation());

        return back()->with('success', 'Invitation resent successfully.');
    }
}
