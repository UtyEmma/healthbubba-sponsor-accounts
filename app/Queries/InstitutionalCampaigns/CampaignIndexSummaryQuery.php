<?php

namespace App\Queries\InstitutionalCampaigns;

use App\DTOs\Campaigns\CampaignIndexSummaryData;
use App\Enums\CampaignStatus;
use App\Enums\WorkspaceBeneficiaries\WorkspaceBeneficiaryStatus;
use App\Models\Campaign;
use App\Models\Workspace;
use App\Models\WorkspaceBeneficiary;
use App\ValueObjects\Money;

final readonly class CampaignIndexSummaryQuery
{
    public function __construct(private CampaignMetricsQuery $metrics) {}

    public function get(Workspace $workspace): CampaignIndexSummaryData
    {
        $campaigns = Campaign::query()
            ->whereBelongsTo($workspace)
            ->get();
        $this->metrics->hydrate($campaigns);
        $wallet = $workspace->wallet()->firstOrFail(['balance', 'currency']);
        $currency = $wallet->currency;
        $availableBalance = $wallet->balance;
        $allocated = new Money(0, $currency);
        $utilized = new Money(0, $currency);
        $allocatedCampaigns = 0;

        foreach ($campaigns as $campaign) {
            /** @var array<string, mixed> $campaignMetrics */
            $campaignMetrics = $campaign->getAttribute('financial_metrics');
            $utilized = $utilized->add(Money::fromMajor((string) $campaignMetrics['utilized'], $currency));

            if ($campaign->lifecycleStatus() !== CampaignStatus::COMPLETED) {
                $reserved = Money::fromMajor((string) $campaignMetrics['reserved'], $currency);
                $allocated = $allocated->add($reserved);

                if ($reserved->amountInMinorUnits > 0) {
                    $allocatedCampaigns++;
                }
            }
        }

        $enrolled = WorkspaceBeneficiary::query()
            ->whereBelongsTo($workspace)
            ->where('relatable_type', (new Campaign)->getMorphClass())
            ->where(function ($query): void {
                $query->whereIn('status', [
                    WorkspaceBeneficiaryStatus::Active,
                    WorkspaceBeneficiaryStatus::Suspended,
                ])->orWhere(function ($query): void {
                    $query->where('status', WorkspaceBeneficiaryStatus::Pending)
                        ->where('expires_at', '>', now());
                });
            })
            ->count();

        return new CampaignIndexSummaryData(
            currency: $currency,
            availableBalance: (string) $availableBalance,
            allocatedBalance: $allocated->toMajorAmount(),
            allocatedCampaigns: $allocatedCampaigns,
            utilized: $utilized->toMajorAmount(),
            enrolledBeneficiaries: $enrolled,
        );
    }
}
