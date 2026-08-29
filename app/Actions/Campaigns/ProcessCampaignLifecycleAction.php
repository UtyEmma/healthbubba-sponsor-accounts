<?php

namespace App\Actions\Campaigns;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\CampaignRecurringCost;

final readonly class ProcessCampaignLifecycleAction
{
    public function __construct(
        private EndCampaignAction $endCampaign,
        private ReconcileCampaignRefundAction $refund,
        private BillCampaignRecurringCostAction $billRecurringCost,
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

        CampaignRecurringCost::query()
            ->where('is_active', true)
            ->whereDate('starts_on', '<=', today())
            ->where(function ($query): void {
                $query->whereNull('ends_on')->orWhereDate('ends_on', '>=', today());
            })
            ->eachById(function (CampaignRecurringCost $cost) use (&$processed): void {
                if ($this->billRecurringCost->execute($cost)) {
                    $processed++;
                }
            });

        return $processed;
    }
}
