<?php

namespace App\DTOs\Campaigns;

use App\Models\Campaign;
use App\Models\User;
use App\Models\Workspace;

final readonly class AddCampaignBoothsData
{
    public function __construct(
        public Workspace $workspace,
        public User $user,
        public Campaign $campaign,
        public int $count,
        public string $preferredDeploymentDate,
        public string $site,
        public string $community,
        public int $expectedBeneficiaries,
        public string $contactName,
        public string $contactPhone,
    ) {}
}
