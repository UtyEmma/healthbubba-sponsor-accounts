<?php

namespace App\DTOs\Campaigns;

use App\Enums\CampaignEnrollmentMethod;

final readonly class CampaignEnrollmentData
{
    public function __construct(
        public CampaignEnrollmentMethod $method,
        public int $estimatedBeneficiaries,
    ) {}
}
