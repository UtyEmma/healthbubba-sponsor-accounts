<?php

namespace App\Http\Controllers\InstitutionalCampaigns;

use App\Actions\Campaigns\RunCampaignBoothMonthlyDeductionAction;
use App\Http\Requests\InstitutionalCampaigns\ManageCampaignBoothRequest;
use Illuminate\Http\RedirectResponse;

final readonly class BillCampaignBoothController
{
    public function __construct(private RunCampaignBoothMonthlyDeductionAction $runDeduction) {}

    public function __invoke(ManageCampaignBoothRequest $request): RedirectResponse
    {
        $charged = $this->runDeduction->execute($request->booth());

        return back()->with('success', $charged ? 'Monthly booth service deducted.' : 'There is no due booth deduction to run.');
    }
}
