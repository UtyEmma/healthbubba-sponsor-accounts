<?php

namespace App\Services\Payments;

use App\DTOs\Payments\BillingCycleUpgradeQuote;
use App\Enums\AccountTypes;
use App\Enums\Subscriptions\IndividualPlanBillingCycle;
use App\Exceptions\Payments\CheckoutUnavailable;
use App\Models\Subscription;
use App\ValueObjects\Money;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

final readonly class BillingCycleUpgradePricingService
{
    public function __construct(
        private PlanPricingService $planPricing,
        private IndividualPlanIntervalPricingService $intervalPricing,
    ) {}

    public function quote(
        Subscription $subscription,
        IndividualPlanBillingCycle $targetCycle,
        ?CarbonInterface $quotedAt = null,
    ): BillingCycleUpgradeQuote {
        if ($subscription->plan->account_type !== AccountTypes::INDIVIDUAL) {
            throw new CheckoutUnavailable('Billing-cycle changes are available only for Individual subscriptions.');
        }

        $currentCycle = $this->intervalPricing->currentCycle($subscription)
            ?? IndividualPlanBillingCycle::MONTHLY;

        if ($targetCycle === $currentCycle) {
            throw new CheckoutUnavailable('This is already your current billing cycle.');
        }

        if ($targetCycle->months() < $currentCycle->months()) {
            throw new CheckoutUnavailable(
                'Billing-cycle downgrades are not available. Cancel the current subscription and purchase a new subscription with the shorter billing cycle.',
            );
        }

        $this->intervalPricing->price($subscription->plan, $targetCycle);
        $current = $this->planPricing->renewal($subscription, $currentCycle)->money;
        $target = $this->planPricing->renewal($subscription, $targetCycle)->money;

        if ($current->currency !== $target->currency
            || $target->amountInMinorUnits <= $current->amountInMinorUnits) {
            throw new CheckoutUnavailable('The selected billing cycle does not produce a payable upgrade.');
        }

        $targetEndsAt = $this->intervalPricing->termEndsAt(
            $subscription->plan,
            $subscription->starts_at,
            $targetCycle,
        );

        if (! $targetEndsAt->greaterThan($subscription->ends_at)) {
            throw new CheckoutUnavailable('The selected billing cycle does not extend the current subscription term.');
        }

        return new BillingCycleUpgradeQuote(
            currentCycle: $currentCycle,
            targetCycle: $targetCycle,
            currentAmount: $current,
            targetAmount: $target,
            amountDueNow: new Money(
                $target->amountInMinorUnits - $current->amountInMinorUnits,
                $target->currency,
            ),
            currentEndsAt: $subscription->ends_at,
            targetEndsAt: $targetEndsAt,
            quotedAt: $quotedAt ?? CarbonImmutable::now(),
        );
    }
}
