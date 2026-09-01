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
        $pendingCharge = $serviceCost?->relationLoaded('charges') === true
            ? $serviceCost->charges->firstWhere('status', CampaignBoothChargeStatus::Pending)
            : null;
        $nextDeduction = in_array($this->status->value, ['active', 'grace_period', 'suspended'], true)
            ? $serviceCost?->next_charge_on
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
            'billingGraceEndsOn' => $this->billing_grace_ends_on?->toDateString(),
            'billingSuspendedAt' => $this->billing_suspended_at?->toISOString(),
            'outstandingAmount' => $pendingCharge?->amount,
            'paidPeriods' => $paidCharges->count(),
            'enrolledOnSite' => $this->whenCounted('beneficiaries'),
        ];
    }
}
