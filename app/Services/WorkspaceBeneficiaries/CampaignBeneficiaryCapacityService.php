<?php

namespace App\Services\WorkspaceBeneficiaries;

use App\DTOs\WorkspaceBeneficiaries\CapacitySummary;
use App\Enums\WorkspaceBeneficiaries\WorkspaceBeneficiaryStatus;
use App\Models\Campaign;
use App\Models\Workspace;

final readonly class CampaignBeneficiaryCapacityService
{
    public function summary(Campaign $campaign): CapacitySummary
    {
        $this->expirePending($campaign);

        $used = $this->used($campaign);

        return new CapacitySummary(
            used: $used,
            total: max($used, $campaign->beneficiary_limit ?? $campaign->estimated_beneficiaries ?? 0),
            unlimited: $campaign->beneficiary_limit === null,
        );
    }

    public function lockCampaign(Workspace $workspace, Campaign $campaign): Campaign
    {
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
