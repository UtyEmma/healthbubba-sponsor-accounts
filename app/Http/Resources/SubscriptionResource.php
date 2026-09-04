<?php

namespace App\Http\Resources;

use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;
use Revoltify\Subscriptionify\Enums\SubscriptionStatus;

/** @mixin Subscription */
final class SubscriptionResource extends JsonResource
{
    public function __construct(
        Subscription $resource,
        private readonly string $renewalAmount,
    ) {
        parent::__construct($resource);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $status = $this->status->value;

        return [
            'id' => (int) $this->getKey(),
            'status' => $status,
            'statusLabel' => Str::of($status)->replace('_', ' ')->title()->toString(),
            'isValid' => $this->valid(),
            'plan' => $this->whenLoaded('plan', fn (): array => [
                'id' => (int) $this->plan->getKey(),
                'name' => $this->plan->name,
                'price' => $this->plan->price,
                'billingLabel' => $this->billingLabel(),
            ]),
            'startsAt' => $this->starts_at->toISOString(),
            'endsAt' => $this->ends_at?->toISOString(),
            'trialEndsAt' => $this->trial_ends_at?->toISOString(),
            'cancelledAt' => $this->cancelled_at?->toISOString(),
            'renewedAt' => $this->renewed_at?->toISOString(),
            'autoRenew' => $this->auto_renew,
            'nextChargeAt' => $this->next_charge_at?->toISOString(),
            'capacityCount' => $this->capacity_count,
            'renewalAttempts' => $this->renewal_attempts,
            'renewalAmount' => $this->renewalAmount,
            'renewalPaymentAvailable' => $this->renewalPaymentAvailable(),
            'scheduledPlan' => $this->whenLoaded('scheduledPlan', fn (): ?array => $this->scheduledPlan === null
                ? null
                : [
                    'id' => (int) $this->scheduledPlan->getKey(),
                    'name' => $this->scheduledPlan->name,
                    'billingLabel' => $this->billingLabelFor($this->scheduledPlan),
                ]),
            'scheduledPlanChangeAt' => $this->scheduled_plan_change_at?->toISOString(),
        ];
    }

    private function renewalPaymentAvailable(): bool
    {
        if ($this->status === SubscriptionStatus::PastDue) {
            return true;
        }

        if ($this->status !== SubscriptionStatus::Active) {
            return false;
        }

        return $this->renewal_retry_at?->isPast() === true
            || $this->next_charge_at?->isPast() === true;
    }

    private function billingLabel(): string
    {
        return $this->billingLabelFor($this->plan);
    }

    private function billingLabelFor(Plan $plan): string
    {
        $period = $plan->billing_period;
        $interval = $plan->billing_interval->value;

        return $period === 1
            ? "per {$interval}"
            : "every {$period} {$interval}s";
    }
}
