<?php

namespace App\Actions\Campaigns;

use App\Enums\CampaignBudgetCategory;
use App\Models\Campaign;
use App\Models\CampaignBudgetUsage;
use App\ValueObjects\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RecordCampaignBudgetUsageAction
{
    /** @param array<string, mixed> $meta */
    public function execute(
        Campaign $campaign,
        CampaignBudgetCategory $category,
        Money $amount,
        string $reference,
        array $meta = [],
    ): CampaignBudgetUsage {
        return DB::transaction(function () use ($campaign, $category, $amount, $reference, $meta): CampaignBudgetUsage {
            $campaign = Campaign::query()->whereKey($campaign->getKey())->lockForUpdate()->firstOrFail();

            if (! $campaign->isActive()) {
                throw ValidationException::withMessages(['campaign' => 'This campaign is not active.']);
            }

            $budget = Money::fromMajor(
                $category === CampaignBudgetCategory::Medication
                    ? $campaign->medication_budget
                    : $campaign->laboratory_budget,
                $campaign->currency,
            );
            $used = Money::fromMajor(
                (string) $campaign->budgetUsages()->where('category', $category)->sum('amount'),
                $campaign->currency,
            );

            if ($amount->currency !== $budget->currency
                || $used->amountInMinorUnits + $amount->amountInMinorUnits > $budget->amountInMinorUnits) {
                throw ValidationException::withMessages(['amount' => 'This expense exceeds the remaining campaign budget.']);
            }

            return $campaign->budgetUsages()->create([
                'workspace_id' => $campaign->workspace_id,
                'category' => $category,
                'amount' => $amount->toMajorAmount(),
                'currency' => $amount->currency,
                'reference' => $reference,
                'occurred_at' => now(),
                'meta' => $meta,
            ]);
        }, 3);
    }
}
