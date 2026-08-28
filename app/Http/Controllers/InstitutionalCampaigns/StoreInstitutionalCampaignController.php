<?php

namespace App\Http\Controllers\InstitutionalCampaigns;

use App\Actions\Campaigns\CreateCampaignAction;
use App\Http\Requests\InstitutionalCampaigns\StoreInstitutionalCampaignRequest;
use Illuminate\Http\RedirectResponse;

final readonly class StoreInstitutionalCampaignController
{
    public function __construct(private CreateCampaignAction $createCampaign) {}

    public function __invoke(StoreInstitutionalCampaignRequest $request): RedirectResponse
    {
        $this->createCampaign->execute($request->campaignData());

        return to_route('campaigns.index')->with('success', 'Campaign launched successfully.');
    }
}
