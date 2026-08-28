<?php

namespace App\DTOs\Campaigns;

final readonly class CampaignCreationConfigurationData
{
    public function __construct(
        public string $currency,
        public string $walletBalance,
        public string $gpUnitFee,
        public string $specialistUnitFee,
        public string $boothSetupUnitFee,
        public string $boothMonthlyUnitFee,
    ) {}
}
