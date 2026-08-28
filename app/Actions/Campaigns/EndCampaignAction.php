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
        $campaign = DB::transaction(function () use ($campaign): Campaign {
            $campaign = Campaign::query()->whereKey($campaign->getKey())->lockForUpdate()->firstOrFail();

            if ($campaign->status !== CampaignStatus::COMPLETED) {
                $campaign->update([
                    'status' => CampaignStatus::COMPLETED,
                    'ended_at' => now(),
                    'paused_at' => null,
                    'booth_deactivated_at' => $campaign->booth_activated_at !== null
                        ? now()
                        : $campaign->booth_deactivated_at,
                ]);
            }

            return $campaign;
        }, 3);

        $this->refund->execute($campaign);

        return $campaign->refresh();
    }
}
