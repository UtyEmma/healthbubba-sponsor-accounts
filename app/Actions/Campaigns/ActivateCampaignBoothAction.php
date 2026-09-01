<?php

namespace App\Actions\Campaigns;

use App\Enums\CampaignBoothStatus;
use App\Models\Campaign;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ActivateCampaignBoothAction
{
    public function execute(Campaign $campaign): Campaign
    {
        return DB::transaction(function () use ($campaign): Campaign {
            $campaign = Campaign::query()->whereKey($campaign->getKey())->lockForUpdate()->firstOrFail();

            if (! $campaign->booth_required || $campaign->ended_at !== null) {
                throw ValidationException::withMessages(['booth' => 'This campaign has no booth available for activation.']);
            }

            $campaign->update([
                'booth_activated_at' => $campaign->booth_activated_at ?? now(),
                'booth_deactivated_at' => null,
            ]);
            $now = now();
            $campaign->booths()->where('status', CampaignBoothStatus::Requested)->update([
                'status' => CampaignBoothStatus::Active,
                'activated_at' => $now,
                'deactivated_at' => null,
                'billing_grace_ends_on' => null,
                'billing_suspended_at' => null,
                'updated_at' => $now,
            ]);
            $campaign->recurringCosts()->whereNotNull('campaign_booth_id')->update([
                'is_active' => true,
                'starts_on' => today()->toDateString(),
                'next_charge_on' => today()->toDateString(),
                'ends_on' => null,
                'deactivated_at' => null,
                'updated_at' => $now,
            ]);

            return $campaign->refresh();
        });
    }
}
