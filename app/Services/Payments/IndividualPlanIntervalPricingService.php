<?php

namespace App\Services\Payments;

use App\DTOs\Payments\IndividualPlanBillingOption;
use App\Enums\AccountTypes;
use App\Enums\Subscriptions\IndividualPlanBillingCycle;
use App\Exceptions\Payments\CheckoutUnavailable;
use App\Models\Plan;
use App\Models\PlanIntervalPrice;
use App\Models\Subscription;
use App\ValueObjects\Money;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final readonly class IndividualPlanIntervalPricingService
{
    /** @return Collection<int, IndividualPlanBillingOption> */
    public function options(Plan $plan, bool $includeInactive = true): Collection
    {
        $this->ensureIndividualPlan($plan);
        $plan->loadMissing('intervalPrices');
        $prices = $plan->intervalPrices->keyBy(
            fn (PlanIntervalPrice $price): string => $price->billing_cycle->value,
        );

        return collect(IndividualPlanBillingCycle::cases())
            ->map(function (IndividualPlanBillingCycle $cycle) use ($plan, $prices): IndividualPlanBillingOption {
                $record = $prices->get($cycle->value);
                $available = $cycle === IndividualPlanBillingCycle::MONTHLY
                    ? $plan->is_active
                    : $record instanceof PlanIntervalPrice && $record->is_active;
                $price = $cycle === IndividualPlanBillingCycle::MONTHLY
                    ? Money::fromMajor($plan->price, $this->currency())
                    : Money::fromMajor(
                        $record instanceof PlanIntervalPrice ? $record->price : '0.00',
                        $this->currency(),
                    );

                return new IndividualPlanBillingOption(
                    cycle: $cycle,
                    price: $price,
                    savingsPercentage: $this->savingsPercentage($plan, $cycle, $price),
                    available: $available,
                );
            })
            ->when(! $includeInactive, fn (Collection $options): Collection => $options
                ->filter(fn (IndividualPlanBillingOption $option): bool => $option->available)
                ->values());
    }

    public function price(Plan $plan, IndividualPlanBillingCycle $cycle, bool $requireActive = true): Money
    {
        $option = $this->options($plan)->first(
            fn (IndividualPlanBillingOption $option): bool => $option->cycle === $cycle,
        );

        if (! $option instanceof IndividualPlanBillingOption
            || ($requireActive && ! $option->available)
            || $option->price->amountInMinorUnits < 1) {
            throw new CheckoutUnavailable("The {$cycle->label()} billing option is unavailable for this plan.");
        }

        return $option->price;
    }

    public function currentCycle(Subscription $subscription): ?IndividualPlanBillingCycle
    {
        if ($subscription->plan->account_type !== AccountTypes::INDIVIDUAL) {
            return null;
        }

        return $subscription->billing_cycle ?? IndividualPlanBillingCycle::MONTHLY;
    }

    public function renewalCycle(Subscription $subscription): ?IndividualPlanBillingCycle
    {
        return $subscription->scheduled_billing_cycle ?? $this->currentCycle($subscription);
    }

    public function termEndsAt(
        Plan $plan,
        CarbonInterface $startsAt,
        ?IndividualPlanBillingCycle $cycle,
    ): CarbonInterface {
        if ($plan->account_type !== AccountTypes::INDIVIDUAL) {
            return $plan->calculateEndsAt($startsAt);
        }

        return ($cycle ?? IndividualPlanBillingCycle::MONTHLY)->calculateEndsAt($startsAt);
    }

    private function savingsPercentage(
        Plan $plan,
        IndividualPlanBillingCycle $cycle,
        Money $price,
    ): int {
        $monthly = Money::fromMajor($plan->price, $this->currency());
        $baseline = $monthly->amountInMinorUnits * $cycle->months();

        if ($baseline < 1 || $price->amountInMinorUnits >= $baseline) {
            return 0;
        }

        return (int) round(
            (($baseline - $price->amountInMinorUnits) / $baseline) * 100,
            0,
            PHP_ROUND_HALF_UP,
        );
    }

    private function ensureIndividualPlan(Plan $plan): void
    {
        if ($plan->account_type !== AccountTypes::INDIVIDUAL) {
            throw new CheckoutUnavailable('Billing-cycle options are available only for Individual plans.');
        }
    }

    private function currency(): string
    {
        return config()->string('payments.currency', 'NGN');
    }
}
