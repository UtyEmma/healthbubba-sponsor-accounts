<?php

namespace App\Http\Resources;

use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Campaign */
final class CampaignResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $status = $this->lifecycleStatus();
        $hasCapacityCount = $this->getAttribute('capacity_used') !== null;
        $capacityUsed = (int) ($this->getAttribute('capacity_used') ?? 0);

        return [
            'id' => (int) $this->getKey(),
            'name' => $this->name,
            'slug' => $this->slug,
            'country' => $this->country,
            'city' => $this->city,
            'state' => $this->state,
            'location' => $this->location,
            'targetAudience' => $this->target_audience,
            'beneficiaryLimit' => $this->beneficiary_limit,
            'startDate' => $this->start_date?->toDateString(),
            'endDate' => $this->end_date?->toDateString(),
            'status' => $status->value,
            'statusLabel' => $status->label(),
            'boothRequired' => $this->booth_required,
            'beneficiaryCount' => $this->whenCounted('beneficiaries'),
            'activeBeneficiaryCount' => $this->whenCounted('activeBeneficiaries'),
            'capacityUsed' => $this->when($hasCapacityCount, $capacityUsed),
            'capacityRemaining' => $this->when(
                $hasCapacityCount,
                max(0, $this->beneficiary_limit - $capacityUsed),
            ),
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}
