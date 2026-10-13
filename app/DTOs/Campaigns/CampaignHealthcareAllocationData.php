<?php

namespace App\DTOs\Campaigns;

final readonly class CampaignHealthcareAllocationData
{
    public function __construct(
        public int $scheduledUnits,
        public int $instantUnits,
        public string $medicationBudget,
        public string $laboratoryBudget,
    ) {}
}
