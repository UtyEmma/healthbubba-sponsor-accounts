<?php

namespace App\DTOs\InstitutionalOnboarding;

final readonly class InstitutionalCampaignOnboardingData
{
    public function __construct(
        public string $city,
        public string $state,
        public string $campaignName,
        public ?string $campaignLocation,
        public ?string $targetAudience,
        public string $startDate,
        public string $endDate,
        public bool $boothRequired,
    ) {}
}
