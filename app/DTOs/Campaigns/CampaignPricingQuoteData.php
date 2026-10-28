<?php

namespace App\DTOs\Campaigns;

final readonly class CampaignPricingQuoteData
{
    public function __construct(
        public int $tierId,
        public int $beneficiaries,
        public string $beneficiaryUnitFee,
        public string $beneficiarySetupFee,
        public ?string $monthlyServiceFee,
        public string $currency,
    ) {}
}
