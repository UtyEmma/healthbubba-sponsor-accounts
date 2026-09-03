<?php

namespace App\Http\Resources;

use App\DTOs\Campaigns\CampaignIndexSummaryData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CampaignIndexSummaryData */
final class CampaignIndexSummaryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'currency' => $this->currency,
            'availableBalance' => $this->availableBalance,
            'allocatedBalance' => $this->allocatedBalance,
            'allocatedCampaigns' => $this->allocatedCampaigns,
            'utilized' => $this->utilized,
            'enrolledBeneficiaries' => $this->enrolledBeneficiaries,
        ];
    }
}
