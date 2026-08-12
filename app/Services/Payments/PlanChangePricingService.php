<?php

namespace App\Services\Payments;

use App\DTOs\Payments\PlanChangeQuote;
use App\Enums\Subscriptions\PlanChangeDirection;
use App\Exceptions\Payments\CheckoutUnavailable;
use App\Models\Plan;
use App\Models\Subscription;
use App\ValueObjects\Money;
use Carbon\CarbonInterface;

final readonly class PlanChangePricingService
{
    public function __construct(
        private PlanPricingService $plans,
    ) {}

    public function quote(
        Subscription $subscription,
        Plan $targetPlan,
        ?CarbonInterface $quotedAt = null,
    ): PlanChangeQuote {
        if ((int) $subscription->plan_id === (int) $targetPlan->getKey()) {
            throw new CheckoutUnavailable('This is already the current subscription plan.');
        }

        $currentCharge = $this->plans->renewal($subscription);
        $targetCharge = $this->plans->renewalForPlan($subscription, $targetPlan);
        $difference = $targetCharge->money->amountInMinorUnits
            - $currentCharge->money->amountInMinorUnits;

        if ($difference === 0) {
            throw new CheckoutUnavailable('The selected plan has the same recurring price as the current plan.');
        }

        $direction = $difference > 0
            ? PlanChangeDirection::UPGRADE
            : PlanChangeDirection::DOWNGRADE;
        $effectiveAt = $subscription->ends_at;
        $quotedAt ??= now();

        if ($effectiveAt === null || ! $effectiveAt->isAfter($quotedAt)) {
            throw new CheckoutUnavailable('The subscription does not have an active billing term.');
        }

        if (! $subscription->starts_at->isBefore($effectiveAt)) {
            throw new CheckoutUnavailable('The subscription billing term is invalid.');
        }

        $amountDueNow = $direction === PlanChangeDirection::UPGRADE
            ? $this->prorateDifference(
                differenceInMinor: $difference,
                termStartsAt: $subscription->starts_at,
                termEndsAt: $effectiveAt,
                quotedAt: $quotedAt,
                currency: $targetCharge->money->currency,
            )
            : new Money(0, $targetCharge->money->currency);

        return new PlanChangeQuote(
            direction: $direction,
            currentRenewal: $currentCharge->money,
            targetRenewal: $targetCharge->money,
            amountDueNow: $amountDueNow,
            targetCapacityCount: $targetCharge->capacityCount ?? $subscription->capacity_count,
            additionalCapacity: $targetCharge->additionalCapacity,
            effectiveAt: $effectiveAt,
        );
    }

    private function prorateDifference(
        int $differenceInMinor,
        CarbonInterface $termStartsAt,
        CarbonInterface $termEndsAt,
        CarbonInterface $quotedAt,
        string $currency,
    ): Money {
        $termSeconds = max(1, $termStartsAt->diffInSeconds($termEndsAt));
        $remainingSeconds = max(0, $quotedAt->diffInSeconds($termEndsAt));
        $proratedMinor = (int) round(
            $differenceInMinor * min(1, $remainingSeconds / $termSeconds),
            0,
            PHP_ROUND_HALF_UP,
        );

        return new Money(max(1, $proratedMinor), $currency);
    }
}
