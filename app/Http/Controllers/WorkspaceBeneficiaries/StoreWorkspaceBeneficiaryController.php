<?php

namespace App\Http\Controllers\WorkspaceBeneficiaries;

use App\Actions\WorkspaceBeneficiaries\InviteWorkspaceBeneficiaryAction;
use App\Enums\AccountTypes;
use App\Http\Requests\WorkspaceBeneficiaries\StoreWorkspaceBeneficiaryRequest;
use Illuminate\Http\RedirectResponse;

final readonly class StoreWorkspaceBeneficiaryController
{
    public function __construct(private InviteWorkspaceBeneficiaryAction $invite) {}

    public function __invoke(StoreWorkspaceBeneficiaryRequest $request): RedirectResponse
    {
        $this->invite->execute($request->workspace(), $request->user(), $request->invitationData());

        return redirect()->route(
            $request->workspace()->type === AccountTypes::BUSINESS ? 'business.employees' : 'beneficiaries.index',
        )->with('success', 'Invitation sent successfully.');
    }
}
