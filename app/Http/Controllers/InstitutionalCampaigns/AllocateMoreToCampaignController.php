<?php

namespace App\Http\Controllers\InstitutionalCampaigns;

use App\Actions\Campaigns\AllocateMoreToCampaignAction;
use App\Http\Requests\InstitutionalCampaigns\AllocateMoreToCampaignRequest;
use Illuminate\Http\RedirectResponse;

final readonly class AllocateMoreToCampaignController
{
    public function __construct(private AllocateMoreToCampaignAction $allocate) {}

    public function __invoke(AllocateMoreToCampaignRequest $request): RedirectResponse
    {
        $this->allocate->execute($request->dto());

        return back()->with('success', 'Additional funds allocated to the campaign.');
    }
}
