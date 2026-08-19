<?php

namespace App\Services\WorkspaceBeneficiaries;

use App\DTOs\WorkspaceBeneficiaries\CapacitySummary;
use App\Enums\WorkspaceBeneficiaries\WorkspaceBeneficiaryStatus;
use App\Models\Campaign;
use App\Models\Workspace;

final readonly class CampaignBeneficiaryCapacityService
{
    public function __construct(
        private WorkspaceBeneficiaryCapacityService $workspaceCapacity,
    ) {}

    public function summary(Campaign $campaign, Workspace $workspace): CapacitySummary
    {
        $this->expirePending($campaign);

        $workspaceSummary = $this->workspaceCapacity->summary($workspace);

        return new CapacitySummary(
            used: $this->used($campaign),
            total: max(0, $campaign->beneficiary_limit),
            unavailableReason: $workspaceSummary->unavailableReason,
        );
    }

    public function lockCampaign(Workspace $workspace, Campaign $campaign): Campaign {
        return Campaign::query()
            ->whereBelongsTo($workspace)
            ->whereKey($campaign->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function used(Campaign $campaign): int
    {
        return $campaign->beneficiaries()->consumingCapacity()->count();
    }

    public function expirePending(Campaign $campaign): int
    {
        return $campaign->beneficiaries()
            ->where('status', WorkspaceBeneficiaryStatus::Pending)
            ->where('expires_at', '<=', now())
            ->update(['status' => WorkspaceBeneficiaryStatus::Expired]);
    }
}
