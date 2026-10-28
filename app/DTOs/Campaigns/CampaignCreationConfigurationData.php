<?php

namespace App\DTOs\Campaigns;

final readonly class CampaignCreationConfigurationData
{
    /** @param list<CampaignPricingTierData> $pricingTiers */
    public function __construct(
        public string $currency,
        public string $walletBalance,
        public string $scheduledUnitFee,
        public string $instantUnitFee,
        public string $boothSetupUnitFee,
        public string $boothMonthlyUnitFee,
        public array $pricingTiers = [],
    ) {}
}
