<?php

namespace App\Http\Controllers\WorkspaceBeneficiaries;

use App\Actions\WorkspaceBeneficiaries\CancelWorkspaceBeneficiaryInvitationAction;
use App\Http\Requests\WorkspaceBeneficiaries\ManageWorkspaceBeneficiaryRequest;
use App\Models\WorkspaceBeneficiary;
use Illuminate\Http\RedirectResponse;

final readonly class CancelWorkspaceBeneficiaryInvitationController
{
    public function __construct(private CancelWorkspaceBeneficiaryInvitationAction $cancel) {}

    public function __invoke(WorkspaceBeneficiary $workspaceBeneficiary, ManageWorkspaceBeneficiaryRequest $request): RedirectResponse
    {
        $this->cancel->execute($request->workspace(), $request->user(), $request->invitation());

        return back()->with('success', 'Invitation cancelled.');
    }
}
