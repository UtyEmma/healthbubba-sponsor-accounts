<?php

namespace App\Http\Controllers\InstitutionalCampaigns;

use App\Actions\Campaigns\RecordCampaignUsageAction;
use App\Http\Requests\InstitutionalCampaigns\RecordCampaignUsageRequest;
use Illuminate\Http\RedirectResponse;

final readonly class RecordCampaignUsageController
{
    public function __construct(private RecordCampaignUsageAction $record) {}

    public function __invoke(RecordCampaignUsageRequest $request): RedirectResponse
    {
        $this->record->execute($request->dto());

        return back()->with('success', 'Campaign usage recorded.');
    }
}
