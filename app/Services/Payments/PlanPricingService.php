<?php

namespace App\Services\Payments;

use App\DTOs\CapacityPurchases\CapacityConfiguration;
use App\DTOs\Payments\PlanCharge;
use App\Enums\AccountTypes;
use App\Enums\Subscriptions\IndividualPlanBillingCycle;
use App\Exceptions\Payments\CheckoutUnavailable;
use App\Models\Plan;
use App\Models\Subscription;
use App\ValueObjects\Money;
use InvalidArgumentException;

final readonly class PlanPricingService
{
    public function __construct(
        private CapacityPricingService $capacityPricing,
        private IndividualPlanIntervalPricingService $intervalPricing,
    ) {}

    public function checkout(
        Plan $plan,
        int $additionalCapacity,
        ?IndividualPlanBillingCycle $billingCycle = null,
    ): PlanCharge {
        $this->ensurePlanIsBillable($plan);
        $billingCycle = $this->normalizedCycle($plan, $billingCycle);

        if ($additionalCapacity < 0 || $additionalCapacity > 100000) {
            throw new CheckoutUnavailable('Additional capacity must be between 0 and 100,000.');
        }

        $configuration = $this->capacityPricing->configuration($plan, $billingCycle);
        $basePrice = $this->basePrice($plan, $billingCycle);

        if (! $configuration instanceof CapacityConfiguration) {
            if ($additionalCapacity > 0) {
                throw new CheckoutUnavailable('Additional capacity is not available for this plan.');
            }

            return new PlanCharge(
                money: $basePrice,
                capacityCount: null,
                additionalCapacity: 0,
            );
        }

        if ($configuration->includedCapacity < 1 && $additionalCapacity > 0) {
            throw new CheckoutUnavailable('The included capacity has not been configured for this plan.');
        }

        $this->ensureAdditionalCapacityIsAvailable($configuration, $additionalCapacity);
        $money = $basePrice;

        if ($additionalCapacity > 0 && $configuration->unitPrice instanceof Money) {
            $money = $money->add($configuration->unitPrice->multiply($additionalCapacity));
        }

        return new PlanCharge(
            money: $money,
            capacityCount: max(1, $configuration->includedCapacity) + $additionalCapacity,
            additionalCapacity: $additionalCapacity,
        );
    }

    public function renewal(
        Subscription $subscription,
        ?IndividualPlanBillingCycle $billingCycle = null,
    ): PlanCharge {
        return $this->renewalForPlan($subscription, $subscription->plan, $billingCycle);
    }

    public function renewalForPlan(
        Subscription $subscription,
        Plan $plan,
        ?IndividualPlanBillingCycle $billingCycle = null,
    ): PlanCharge {
        $this->ensurePlanIsBillable($plan);
        $billingCycle = $this->normalizedCycle(
            $plan,
            $billingCycle ?? $this->intervalPricing->renewalCycle($subscription),
        );

        if ($plan->account_type !== $subscription->plan->account_type) {
            throw new CheckoutUnavailable('The selected plan is not available for this subscription.');
        }

        $configuration = $this->capacityPricing->configuration($plan, $billingCycle);
        $basePrice = $this->basePrice($plan, $billingCycle, requireActive: false);

        if (! $configuration instanceof CapacityConfiguration) {
            return new PlanCharge(
                money: $basePrice,
                capacityCount: null,
                additionalCapacity: 0,
            );
        }

        $capacityCount = max(
            $subscription->capacity_count,
            max(1, $configuration->includedCapacity),
        );

        if ($configuration->maximumCapacity !== null
            && $capacityCount > $configuration->maximumCapacity) {
            throw new CheckoutUnavailable(
                "The subscription capacity exceeds the {$plan->name} plan maximum.",
            );
        }

        $additionalCapacity = $configuration->additionalCapacity($capacityCount);
        $money = $basePrice;

        if ($additionalCapacity > 0) {
            if (! $configuration->unitPrice instanceof Money) {
                throw new CheckoutUnavailable('The purchased capacity cannot be priced for renewal.');
            }

            $money = $money->add($configuration->unitPrice->multiply($additionalCapacity));
        }

        return new PlanCharge(
            money: $money,
            capacityCount: $capacityCount,
            additionalCapacity: $additionalCapacity,
        );
    }

    private function ensureAdditionalCapacityIsAvailable(
        CapacityConfiguration $configuration,
        int $additionalCapacity,
    ): void {
        if ($additionalCapacity === 0) {
            return;
        }

        if (! $configuration->purchasesEnabled || ! $configuration->unitPrice instanceof Money) {
            throw new CheckoutUnavailable(
                $configuration->unavailableReason ?? 'Additional capacity purchases are unavailable.',
            );
        }

        if ($configuration->maximumCapacity !== null
            && $configuration->includedCapacity + $additionalCapacity > $configuration->maximumCapacity) {
            throw new CheckoutUnavailable(
                "This plan supports at most {$configuration->maximumCapacity} {$configuration->unitPlural}.",
            );
        }
    }

    private function ensurePlanIsBillable(Plan $plan): void
    {
        if (! $plan->is_active || $plan->is_free || $plan->trial_days > 0) {
            throw new CheckoutUnavailable('The plan is not eligible for recurring online checkout.');
        }

        try {
            $base = Money::fromMajor($plan->price, $this->currency());
        } catch (InvalidArgumentException $exception) {
            throw new CheckoutUnavailable('The plan price is not valid for online checkout.', previous: $exception);
        }

        if ($base->amountInMinorUnits === 0) {
            throw new CheckoutUnavailable('A positive plan price is required for online checkout.');
        }
    }

    private function normalizedCycle(
        Plan $plan,
        ?IndividualPlanBillingCycle $billingCycle,
    ): ?IndividualPlanBillingCycle {
        if ($plan->account_type !== AccountTypes::INDIVIDUAL) {
            return null;
        }

        return $billingCycle ?? IndividualPlanBillingCycle::MONTHLY;
    }

    private function basePrice(
        Plan $plan,
        ?IndividualPlanBillingCycle $billingCycle,
        bool $requireActive = true,
    ): Money {
        if ($plan->account_type !== AccountTypes::INDIVIDUAL) {
            return Money::fromMajor($plan->price, $this->currency());
        }

        return $this->intervalPricing->price(
            $plan,
            $billingCycle ?? IndividualPlanBillingCycle::MONTHLY,
            $requireActive,
        );
    }

    private function currency(): string
    {
        return config()->string('payments.currency', 'NGN');
    }
}
