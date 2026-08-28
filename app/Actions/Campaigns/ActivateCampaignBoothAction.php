<?php

namespace App\Actions\Campaigns;

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

            return $campaign->refresh();
        });
    }
}
