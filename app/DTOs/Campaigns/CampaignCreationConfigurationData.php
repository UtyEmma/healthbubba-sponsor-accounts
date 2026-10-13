<?php

namespace App\DTOs\Campaigns;

final readonly class CampaignCreationConfigurationData
{
    public function __construct(
        public string $currency,
        public string $walletBalance,
        public string $scheduledUnitFee,
        public string $instantUnitFee,
        public string $boothSetupUnitFee,
        public string $boothMonthlyUnitFee,
    ) {}
}
