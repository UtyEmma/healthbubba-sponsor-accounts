<?php

namespace App\Actions\Campaigns;

use App\Enums\CampaignRecurringCostCategory;
use App\Models\CampaignBooth;
use App\Models\CampaignRecurringCost;

final readonly class RunCampaignBoothMonthlyDeductionAction
{
    public function __construct(
        private BillCampaignRecurringCostAction $billRecurringCost,
    ) {}

    public function execute(CampaignBooth $booth): bool
    {
        $serviceCost = CampaignRecurringCost::query()
            ->whereBelongsTo($booth, 'booth')
            ->where('category', CampaignRecurringCostCategory::BoothService)
            ->firstOrFail();

        return $this->billRecurringCost->execute($serviceCost);
    }
}
