<?php

namespace App\Http\Controllers\InstitutionalCampaigns;

use App\Actions\Campaigns\ResumeCampaignAction;
use App\Http\Requests\InstitutionalCampaigns\ChangeCampaignLifecycleRequest;
use Illuminate\Http\RedirectResponse;

final readonly class ResumeCampaignController
{
    public function __construct(private ResumeCampaignAction $resume) {}

    public function __invoke(ChangeCampaignLifecycleRequest $request): RedirectResponse
    {
        $this->resume->execute($request->campaign());

        return back()->with('success', 'Campaign resumed.');
    }
}
