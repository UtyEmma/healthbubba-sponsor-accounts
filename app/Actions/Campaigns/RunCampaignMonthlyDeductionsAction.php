<?php

namespace App\Actions\Campaigns;

use App\DTOs\Campaigns\CampaignMonthlyDeductionResult;
use App\Models\Campaign;
use App\Models\CampaignRecurringCost;

final readonly class RunCampaignMonthlyDeductionsAction
{
    public function __construct(
        private BillCampaignRecurringCostAction $billRecurringCost,
    ) {}

    public function execute(Campaign $campaign): CampaignMonthlyDeductionResult
    {
        $costs = CampaignRecurringCost::query()
            ->whereBelongsTo($campaign)
            ->where('is_active', true)
            ->oldest('id')
            ->get();

        $chargesCompleted = 0;

        foreach ($costs as $cost) {
            if ($this->billRecurringCost->execute($cost)) {
                $chargesCompleted++;
            }
        }

        return new CampaignMonthlyDeductionResult(
            costsChecked: $costs->count(),
            chargesCompleted: $chargesCompleted,
        );
    }
}
