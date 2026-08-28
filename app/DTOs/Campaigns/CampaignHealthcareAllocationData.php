<?php

namespace App\DTOs\Campaigns;

final readonly class CampaignHealthcareAllocationData
{
    public function __construct(
        public int $gpUnits,
        public int $specialistUnits,
        public string $medicationBudget,
        public string $laboratoryBudget,
    ) {}
}
