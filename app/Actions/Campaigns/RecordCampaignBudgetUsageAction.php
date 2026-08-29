<?php

namespace App\Actions\Campaigns;

use App\Enums\CampaignBudgetCategory;
use App\Enums\CampaignUsageBenefit;
use App\Enums\CampaignUsageSource;
use App\Models\Campaign;
use App\Models\CampaignUsageEntry;
use App\Support\Payments\PaymentReferenceGenerator;
use App\ValueObjects\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class RecordCampaignBudgetUsageAction
{
    public function __construct(private PaymentReferenceGenerator $references) {}

    /** @param array<string, mixed> $meta */
    public function execute(
        Campaign $campaign,
        CampaignBudgetCategory $category,
        Money $amount,
        string $reference,
        array $meta = [],
    ): CampaignUsageEntry {
        return DB::transaction(function () use ($campaign, $category, $amount, $reference, $meta): CampaignUsageEntry {
            $campaign = Campaign::query()->whereKey($campaign->getKey())->lockForUpdate()->firstOrFail();
            $existing = CampaignUsageEntry::query()
                ->where('source_reference', $reference)
                ->first();

            if ($existing instanceof CampaignUsageEntry) {
                return $existing;
            }

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
                (string) $campaign->usageEntries()->where('benefit', $category->value)->sum('total_amount'),
                $campaign->currency,
            );

            if ($amount->currency !== $budget->currency
                || $used->amountInMinorUnits + $amount->amountInMinorUnits > $budget->amountInMinorUnits) {
                throw ValidationException::withMessages(['amount' => 'This expense exceeds the remaining campaign budget.']);
            }

            return $campaign->usageEntries()->create([
                'workspace_id' => $campaign->workspace_id,
                'benefit' => CampaignUsageBenefit::from($category->value),
                'total_amount' => $amount->toMajorAmount(),
                'currency' => $amount->currency,
                'source' => CampaignUsageSource::Provider,
                'source_reference' => $reference,
                'reference' => $this->references->generateCampaignUsage(),
                'occurred_at' => now(),
                'meta' => $meta,
            ]);
        }, 3);
    }
}
