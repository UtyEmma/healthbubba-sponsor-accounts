<?php

namespace App\Actions\Campaigns;

use App\Models\Campaign;
use Illuminate\Support\Facades\DB;

final class DeactivateCampaignBoothAction
{
    public function execute(Campaign $campaign): Campaign
    {
        return DB::transaction(function () use ($campaign): Campaign {
            $campaign = Campaign::query()->whereKey($campaign->getKey())->lockForUpdate()->firstOrFail();
            $campaign->update(['booth_deactivated_at' => now()]);

            return $campaign->refresh();
        });
    }
}
