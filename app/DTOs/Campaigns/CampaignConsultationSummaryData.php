<?php

namespace App\DTOs\Campaigns;

final readonly class CampaignConsultationSummaryData
{
    /**
     * @param  list<array<string, mixed>>  $allocations
     */
    public function __construct(
        public string $campaignName,
        public int $activeBeneficiaries,
        public array $allocations,
        public string $currency,
        public string $walletBalance,
        public string $scheduledSpent,
        public string $instantSpent,
        public string $totalSpent,
    ) {}
}
