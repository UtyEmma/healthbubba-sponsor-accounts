<?php

namespace App\Http\Controllers\InstitutionalCampaigns;

use App\Actions\Campaigns\PauseCampaignAction;
use App\Http\Requests\InstitutionalCampaigns\ChangeCampaignLifecycleRequest;
use Illuminate\Http\RedirectResponse;

final readonly class PauseCampaignController
{
    public function __construct(private PauseCampaignAction $pause) {}

    public function __invoke(ChangeCampaignLifecycleRequest $request): RedirectResponse
    {
        $this->pause->execute($request->campaign());

        return back()->with('success', 'Campaign paused.');
    }
}
