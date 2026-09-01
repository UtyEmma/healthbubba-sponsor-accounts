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
            $now = now();
            $campaign->update(['booth_deactivated_at' => $now]);
            $campaign->booths()->whereNull('deactivated_at')->update([
                'status' => 'inactive',
                'deactivated_at' => $now,
                'billing_grace_ends_on' => null,
                'updated_at' => $now,
            ]);
            $campaign->recurringCosts()->whereNotNull('campaign_booth_id')->where('is_active', true)->update([
                'is_active' => false,
                'ends_on' => today()->toDateString(),
                'deactivated_at' => $now,
                'updated_at' => $now,
            ]);

            return $campaign->refresh();
        });
    }
}
