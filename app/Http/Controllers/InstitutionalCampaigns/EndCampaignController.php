<?php

namespace App\Http\Controllers\InstitutionalCampaigns;

use App\Actions\Campaigns\EndCampaignAction;
use App\Http\Requests\InstitutionalCampaigns\ChangeCampaignLifecycleRequest;
use Illuminate\Http\RedirectResponse;

final readonly class EndCampaignController
{
    public function __construct(private EndCampaignAction $end) {}

    public function __invoke(ChangeCampaignLifecycleRequest $request): RedirectResponse
    {
        $this->end->execute($request->campaign());

        return back()->with('success', 'Campaign ended and unused allocation returned.');
    }
}
