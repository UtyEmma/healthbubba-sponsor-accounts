<?php

namespace App\Actions\Campaigns;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ResumeCampaignAction
{
    public function execute(Campaign $campaign): Campaign
    {
        return DB::transaction(function () use ($campaign): Campaign {
            $campaign = Campaign::query()->whereKey($campaign->getKey())->lockForUpdate()->firstOrFail();

            if ($campaign->status !== CampaignStatus::PAUSED || $campaign->end_date?->isPast() === true) {
                throw ValidationException::withMessages(['campaign' => 'This campaign cannot be resumed.']);
            }

            $campaign->update(['status' => CampaignStatus::IN_PROGRESS, 'paused_at' => null]);

            return $campaign->refresh();
        });
    }
}
