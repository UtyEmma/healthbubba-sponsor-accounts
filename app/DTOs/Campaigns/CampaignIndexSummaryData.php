<?php

namespace App\DTOs\Campaigns;

final readonly class CampaignIndexSummaryData
{
    public function __construct(
        public string $currency,
        public string $availableBalance,
        public string $allocatedBalance,
        public int $allocatedCampaigns,
        public string $utilized,
        public int $enrolledBeneficiaries,
    ) {}
}
