<?php

namespace App\DTOs\Campaigns;

use App\Models\Campaign;
use App\Models\User;
use App\Models\Workspace;

final readonly class AllocateMoreToCampaignData
{
    public function __construct(
        public Workspace $workspace,
        public User $user,
        public Campaign $campaign,
        public int $gpUnits,
        public int $specialistUnits,
        public string $medicationBudget,
        public string $laboratoryBudget,
    ) {}
}
