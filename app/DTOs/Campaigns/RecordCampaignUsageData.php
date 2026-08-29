<?php

namespace App\DTOs\Campaigns;

use App\Enums\CampaignUsageBenefit;
use App\Models\Campaign;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceBeneficiary;

final readonly class RecordCampaignUsageData
{
    public function __construct(
        public Workspace $workspace,
        public User $user,
        public Campaign $campaign,
        public WorkspaceBeneficiary $beneficiary,
        public CampaignUsageBenefit $benefit,
        public ?int $quantity,
        public ?string $amount,
    ) {}
}
