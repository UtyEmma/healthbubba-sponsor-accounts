<?php

namespace App\Http\Controllers\InstitutionalCampaigns;

use App\Actions\Campaigns\AddCampaignBoothsAction;
use App\Http\Requests\InstitutionalCampaigns\AddCampaignBoothsRequest;
use Illuminate\Http\RedirectResponse;

final readonly class AddCampaignBoothsController
{
    public function __construct(private AddCampaignBoothsAction $add) {}

    public function __invoke(AddCampaignBoothsRequest $request): RedirectResponse
    {
        $this->add->execute($request->dto());

        return back()->with('success', 'Booth setup paid and deployment requested.');
    }
}
