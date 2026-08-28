<?php

namespace App\Http\Resources;

use App\DTOs\WorkspaceBeneficiaries\CapacitySummary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CapacitySummary */
final class CampaignBeneficiaryCapacityResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'used' => $this->used,
            'total' => $this->total,
            'remaining' => $this->remaining(),
            'canInvite' => $this->canInvite(),
            'unlimited' => $this->unlimited,
            'unavailableReason' => $this->unavailableReason,
        ];
    }
}
