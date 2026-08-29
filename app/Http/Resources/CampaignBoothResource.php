<?php

namespace App\Http\Resources;

use App\Enums\CampaignBoothChargeStatus;
use App\Models\CampaignBooth;
use App\Models\CampaignRecurringCost;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CampaignBooth */
final class CampaignBoothResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var CampaignRecurringCost|null $serviceCost */
        $serviceCost = $this->relationLoaded('recurringCosts')
            ? $this->recurringCosts->first()
            : null;
        $paidCharges = $serviceCost?->relationLoaded('charges') === true
            ? $serviceCost->charges->where('status', CampaignBoothChargeStatus::Paid)
            : collect();
        $nextDeduction = $this->status->value === 'active' && $serviceCost?->starts_on !== null
            ? $serviceCost->starts_on->copy()->addMonthsNoOverflow($paidCharges->count())
            : null;

        return [
            'id' => $this->public_id,
            'name' => $this->name,
            'site' => $this->site,
            'community' => $this->community,
            'expectedBeneficiaries' => $this->expected_beneficiaries,
            'contactName' => $this->contact_name,
            'contactPhone' => $this->contact_phone,
            'preferredDeploymentDate' => $this->preferred_deployment_date->toDateString(),
            'setupFee' => $this->setup_fee,
            'monthlyFee' => $this->monthly_fee,
            'currency' => $this->currency,
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
            'setupPaidAt' => $this->setup_paid_at?->toISOString(),
            'activatedAt' => $this->activated_at?->toISOString(),
            'deactivatedAt' => $this->deactivated_at?->toISOString(),
            'paidThrough' => $this->paid_through?->toDateString(),
            'nextDeduction' => $nextDeduction?->toDateString(),
            'paidPeriods' => $paidCharges->count(),
            'enrolledOnSite' => $this->whenCounted('beneficiaries'),
        ];
    }
}
