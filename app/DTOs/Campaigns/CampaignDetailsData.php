<?php

namespace App\DTOs\Campaigns;

final readonly class CampaignDetailsData
{
    public function __construct(
        public string $name,
        public string $description,
        public string $locations,
        public string $startDate,
        public string $endDate,
    ) {}
}
