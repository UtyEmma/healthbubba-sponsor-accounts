<?php

namespace App\DTOs\Campaigns;

use App\Models\User;
use App\Models\Workspace;

final readonly class CreateCampaignData
{
    public function __construct(
        public Workspace $workspace,
        public User $user,
        public CampaignDetailsData $details,
        public CampaignEnrollmentData $enrollment,
        public CampaignHealthcareAllocationData $healthcare,
        public CampaignBoothData $booth,
    ) {}
}
