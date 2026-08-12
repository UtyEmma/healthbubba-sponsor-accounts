<?php

namespace App\Http\Controllers\WorkspaceBeneficiaries;

use App\Actions\WorkspaceBeneficiaries\UpdateWorkspaceBeneficiaryAccessAction;
use App\Enums\WorkspaceBeneficiaries\WorkspaceBeneficiaryAccessAction;
use App\Http\Requests\WorkspaceBeneficiaries\UpdateWorkspaceBeneficiaryAccessRequest;
use App\Models\WorkspaceBeneficiary;
use Illuminate\Http\RedirectResponse;

final readonly class UpdateWorkspaceBeneficiaryAccessController
{
    public function __construct(private UpdateWorkspaceBeneficiaryAccessAction $updateAccess) {}

    public function __invoke(
        WorkspaceBeneficiary $workspaceBeneficiary,
        UpdateWorkspaceBeneficiaryAccessRequest $request,
    ): RedirectResponse {
        $action = $request->accessAction();
        $this->updateAccess->execute($request->workspace(), $request->user(), $request->invitation(), $action);

        return back()->with('success', match ($action) {
            WorkspaceBeneficiaryAccessAction::Suspend => 'Access suspended successfully.',
            WorkspaceBeneficiaryAccessAction::Restore => 'Access restored successfully.',
            WorkspaceBeneficiaryAccessAction::Revoke => 'Access revoked successfully.',
        });
    }
}
