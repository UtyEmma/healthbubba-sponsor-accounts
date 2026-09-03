<?php

namespace App\Actions\Campaigns;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use Illuminate\Support\Facades\DB;

final readonly class EndCampaignAction
{
    public function __construct(private ReconcileCampaignRefundAction $refund) {}

    public function execute(Campaign $campaign): Campaign
    {
        return DB::transaction(function () use ($campaign): Campaign {
            $campaign = Campaign::query()->whereKey($campaign->getKey())->lockForUpdate()->firstOrFail();

            if ($campaign->status !== CampaignStatus::COMPLETED) {
                $now = now();
                $campaign->update([
                    'status' => CampaignStatus::COMPLETED,
                    'ended_at' => $now,
                    'paused_at' => null,
                    'booth_deactivated_at' => $campaign->booth_activated_at !== null
                        ? $now
                        : $campaign->booth_deactivated_at,
                ]);
                $campaign->booths()->whereNull('deactivated_at')->update([
                    'status' => 'inactive',
                    'deactivated_at' => $now,
                    'updated_at' => $now,
                ]);
                $campaign->recurringCosts()->where('is_active', true)->update([
                    'is_active' => false,
                    'ends_on' => today()->toDateString(),
                    'deactivated_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $this->refund->execute($campaign);

            return $campaign->refresh();
        }, 3);
    }
}
