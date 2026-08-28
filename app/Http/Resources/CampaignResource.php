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
            'description' => $this->description,
            'slug' => $this->slug,
            'country' => $this->country,
            'city' => $this->city,
            'state' => $this->state,
            'location' => $this->location,
            'targetAudience' => $this->target_audience,
            'enrollmentMethod' => $this->enrollment_method?->value,
            'estimatedBeneficiaries' => $this->estimated_beneficiaries,
            'beneficiaryLimit' => $this->beneficiary_limit,
            'startDate' => $this->start_date?->toDateString(),
            'endDate' => $this->end_date?->toDateString(),
            'status' => $status->value,
            'statusLabel' => $status->label(),
            'boothRequired' => $this->booth_required,
            'booth' => $this->booth_required ? [
                'count' => $this->booth_count,
                'preferredDeploymentDate' => $this->booth_preferred_deployment_date?->toDateString(),
                'site' => $this->booth_site,
                'contactName' => $this->booth_contact_name,
                'contactPhone' => $this->booth_contact_phone,
                'setupUnitFee' => $this->booth_setup_unit_fee,
                'monthlyUnitFee' => $this->booth_monthly_unit_fee,
                'activatedAt' => $this->booth_activated_at?->toISOString(),
                'deactivatedAt' => $this->booth_deactivated_at?->toISOString(),
            ] : null,
            'gpFee' => $this->gp_fee,
            'specialistFee' => $this->specialist_fee,
            'beneficiaryCount' => $this->whenCounted('beneficiaries'),
            'activeBeneficiaryCount' => $this->whenCounted('activeBeneficiaries'),
            'capacityUsed' => $this->when($hasCapacityCount, $capacityUsed),
            'capacityRemaining' => $this->when(
                $hasCapacityCount && $this->beneficiary_limit !== null,
                $this->beneficiary_limit === null ? null : max(0, $this->beneficiary_limit - $capacityUsed),
            ),
            'financial' => $this->when(
                is_array($this->getAttribute('financial_metrics')),
                $this->getAttribute('financial_metrics'),
            ),
            'launchedAt' => $this->launched_at?->toISOString(),
            'pausedAt' => $this->paused_at?->toISOString(),
            'endedAt' => $this->ended_at?->toISOString(),
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}
