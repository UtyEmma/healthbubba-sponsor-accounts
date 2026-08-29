<?php

namespace App\Http\Controllers\InstitutionalCampaigns;

use App\Actions\Campaigns\DeactivateCampaignBoothRecordAction;
use App\Http\Requests\InstitutionalCampaigns\ManageCampaignBoothRequest;
use Illuminate\Http\RedirectResponse;

final readonly class DeactivateCampaignBoothController
{
    public function __construct(private DeactivateCampaignBoothRecordAction $deactivate) {}

    public function __invoke(ManageCampaignBoothRequest $request): RedirectResponse
    {
        $this->deactivate->execute($request->booth());

        return back()->with('success', 'Booth deactivated. No future monthly fees will be charged.');
    }
}
