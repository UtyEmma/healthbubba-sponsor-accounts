<?php

namespace App\Actions\Campaigns;

use App\Enums\CampaignStatus;
use App\Models\Campaign;

final readonly class ProcessCampaignLifecycleAction
{
    public function __construct(
        private EndCampaignAction $endCampaign,
        private ReconcileCampaignRefundAction $refund,
        private BillCampaignBoothAction $billBooth,
    ) {}

    public function execute(): int
    {
        $processed = Campaign::query()
            ->where('status', CampaignStatus::PENDING)
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())
            ->update(['status' => CampaignStatus::IN_PROGRESS]);

        Campaign::query()
            ->whereIn('status', [CampaignStatus::PENDING, CampaignStatus::IN_PROGRESS, CampaignStatus::PAUSED])
            ->whereDate('end_date', '<', today())
            ->eachById(function (Campaign $campaign) use (&$processed): void {
                $this->endCampaign->execute($campaign);
                $processed++;
            });

        Campaign::query()
            ->where('status', CampaignStatus::COMPLETED)
            ->eachById(function (Campaign $campaign): void {
                $this->refund->execute($campaign);
            });

        Campaign::query()
            ->whereNotNull('booth_activated_at')
            ->whereNull('booth_deactivated_at')
            ->whereNull('ended_at')
            ->eachById(function (Campaign $campaign) use (&$processed): void {
                if ($this->billBooth->execute($campaign)) {
                    $processed++;
                }
            });

        return $processed;
    }
}
