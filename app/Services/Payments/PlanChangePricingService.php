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

        $currency = config()->string('payments.currency', 'NGN');
        $currentBasePrice = Money::fromMajor($subscription->plan->price, $currency);
        $targetBasePrice = Money::fromMajor($targetPlan->price, $currency);
        $difference = $targetBasePrice->amountInMinorUnits
            - $currentBasePrice->amountInMinorUnits;

        if ($difference === 0) {
            throw new CheckoutUnavailable('The selected plan has the same base price as the current plan.');
        }

        $direction = $difference > 0
            ? PlanChangeDirection::UPGRADE
            : PlanChangeDirection::DOWNGRADE;
        $quotedAt ??= now();
        $termEndsAt = $subscription->ends_at;

        if ($termEndsAt === null || ! $termEndsAt->isAfter($quotedAt)) {
            throw new CheckoutUnavailable('The subscription does not have an active billing term.');
        }

        if (! $subscription->starts_at->isBefore($termEndsAt)) {
            throw new CheckoutUnavailable('The subscription billing term is invalid.');
        }

        $currentCharge = $this->plans->renewal($subscription);
        $targetCharge = $this->plans->renewalForPlan($subscription, $targetPlan);

        $amountDueNow = $direction === PlanChangeDirection::UPGRADE
            ? $this->prorateDifference(
                differenceInMinor: $difference,
                termStartsAt: $subscription->starts_at,
                termEndsAt: $termEndsAt,
                quotedAt: $quotedAt,
                currency: $targetCharge->money->currency,
            )
            : new Money(0, $targetCharge->money->currency);

        return new PlanChangeQuote(
            direction: $direction,
            currentBasePrice: $currentBasePrice,
            targetBasePrice: $targetBasePrice,
            currentRenewal: $currentCharge->money,
            targetRenewal: $targetCharge->money,
            amountDueNow: $amountDueNow,
            targetCapacityCount: $targetCharge->capacityCount ?? $subscription->capacity_count,
            additionalCapacity: $targetCharge->additionalCapacity,
            effectiveAt: $quotedAt,
            quotedAt: $quotedAt,
            termEndsAt: $termEndsAt,
        );
    }

    public function direction(Subscription $subscription, Plan $targetPlan): PlanChangeDirection
    {
        $currency = config()->string('payments.currency', 'NGN');
        $current = Money::fromMajor($subscription->plan->price, $currency);
        $target = Money::fromMajor($targetPlan->price, $currency);

        if ($current->amountInMinorUnits === $target->amountInMinorUnits) {
            throw new CheckoutUnavailable('The selected plan has the same base price as the current plan.');
        }

        return $target->amountInMinorUnits > $current->amountInMinorUnits
            ? PlanChangeDirection::UPGRADE
            : PlanChangeDirection::DOWNGRADE;
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
