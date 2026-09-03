<?php

namespace App\Actions\Campaigns;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PauseCampaignAction
{
    public function execute(Campaign $campaign): Campaign
    {
        return DB::transaction(function () use ($campaign): Campaign {
            $campaign = Campaign::query()->whereKey($campaign->getKey())->lockForUpdate()->firstOrFail();

            if ($campaign->lifecycleStatus() !== CampaignStatus::IN_PROGRESS) {
                throw ValidationException::withMessages(['campaign' => 'Only an active campaign can be paused.']);
            }

            $campaign->update(['status' => CampaignStatus::PAUSED, 'paused_at' => now()]);

            return $campaign->refresh();
        });
    }
}
