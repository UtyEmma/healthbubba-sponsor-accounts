<?php

namespace App\Services\Payments;

use App\DTOs\CapacityPurchases\CapacityConfiguration;
use App\DTOs\CapacityPurchases\CapacityPurchaseQuote;
use App\DTOs\CapacityPurchases\CapacityPurchaseSummary;
use App\Enums\AccountTypes;
use App\Enums\Subscriptions\Features;
use App\Exceptions\Payments\CheckoutUnavailable;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Wallet;
use App\ValueObjects\Money;
use Carbon\CarbonInterface;
use InvalidArgumentException;
use Revoltify\Subscriptionify\Models\Feature;
use Revoltify\Subscriptionify\Models\FeaturePlan;

final readonly class CapacityPricingService
{
    public function configuration(Plan $plan): ?CapacityConfiguration
    {
        return match ($plan->account_type) {
            AccountTypes::INDIVIDUAL => $this->individualConfiguration($plan),
            AccountTypes::BUSINESS => $this->businessConfiguration($plan),
            AccountTypes::INSTITUTION => null,
        };
    }

    public function currentCapacity(Subscription $subscription): int
    {
        $configuration = $this->configuration($subscription->plan);

        return max(
            $subscription->capacity_count,
            $configuration->includedCapacity ?? 1,
        );
    }

    public function quote(
        Subscription $subscription,
        int $quantity,
        ?CarbonInterface $quotedAt = null,
    ): CapacityPurchaseQuote {
        $configuration = $this->configuration($subscription->plan);

        if (! $configuration instanceof CapacityConfiguration) {
            throw new CheckoutUnavailable('Additional capacity is not available for this plan.');
        }

        if (! $configuration->purchasesEnabled || ! $configuration->unitPrice instanceof Money) {
            throw new CheckoutUnavailable(
                $configuration->unavailableReason ?? 'Additional capacity purchases are unavailable.',
            );
        }

        if ($quantity < 1 || $quantity > 100000) {
            throw new CheckoutUnavailable('Choose between 1 and 100,000 additional units.');
        }

        $previousCapacity = $this->currentCapacity($subscription);
        $remainingCapacity = $configuration->remainingPurchasableCapacity($previousCapacity);

        if ($remainingCapacity !== null && $quantity > $remainingCapacity) {
            throw new CheckoutUnavailable(
                "This plan allows only {$remainingCapacity} more {$configuration->unitPlural}.",
            );
        }

        $termStartsAt = $subscription->starts_at;
        $termEndsAt = $subscription->ends_at;
        $quotedAt ??= now();

        if ($termEndsAt === null || ! $termEndsAt->isAfter($quotedAt)) {
            throw new CheckoutUnavailable('The subscription does not have an active billable term.');
        }

        if (! $termStartsAt->isBefore($termEndsAt)) {
            throw new CheckoutUnavailable('The subscription billing term is invalid.');
        }

        // dd($configuration, $configuration->unitPrice);
        $proratedUnitPrice = $this->prorate(
            money: $configuration->unitPrice,
            termStartsAt: $termStartsAt,
            termEndsAt: $termEndsAt,
            quotedAt: $quotedAt,
        );

        return new CapacityPurchaseQuote(
            configuration: $configuration,
            quantity: $quantity,
            previousCapacity: $previousCapacity,
            newCapacity: $previousCapacity + $quantity,
            unitPrice: $configuration->unitPrice,
            proratedUnitPrice: $proratedUnitPrice,
            total: $proratedUnitPrice->multiply($quantity),
            renewalIncrease: $configuration->unitPrice->multiply($quantity),
            termStartsAt: $termStartsAt,
            termEndsAt: $termEndsAt,
        );
    }

    public function summary(Subscription $subscription, ?Wallet $wallet): ?CapacityPurchaseSummary
    {
        $configuration = $this->configuration($subscription->plan);

        if (! $configuration instanceof CapacityConfiguration) {
            return null;
        }

        $currentCapacity = $this->currentCapacity($subscription);
        
        $proratedUnitPrice = null;
        $available = $configuration->purchasesEnabled;
        $reason = $configuration->unavailableReason;

        if ($available) {
            try {
                $proratedUnitPrice = $this->quote($subscription, 1)->proratedUnitPrice;
            } catch (CheckoutUnavailable $exception) {
                $available = false;
                $reason = $exception->getMessage();
            }
        }

        return new CapacityPurchaseSummary(
            subscriptionId: (int) $subscription->getKey(),
            unit: $configuration->unit,
            unitPlural: $configuration->unitPlural,
            currentCapacity: $currentCapacity,
            includedCapacity: $configuration->includedCapacity,
            maximumCapacity: $configuration->maximumCapacity,
            unitPrice: $configuration->unitPrice?->toMajorAmount(),
            proratedUnitPrice: $proratedUnitPrice?->toMajorAmount(),
            currency: $configuration->unitPrice->currency
                ?? config()->string('payments.currency', 'NGN'),
            walletBalance: $wallet->balance ?? '0.00',
            available: $available,
            unavailableReason: $reason,
            termEndsAt: $subscription->ends_at?->toISOString(),
        );
    }

    private function individualConfiguration(Plan $plan): CapacityConfiguration
    {
        $plan->loadMissing('features');
        $features = $plan->features->keyBy('slug');
        $included = $this->featureAssignment($features->get(Features::BENEFICIARIES_INCLUDED->value));
        $maximum = $this->featureAssignment($features->get(Features::MAXIMUM_BENEFICIARIES->value));

        $includedCapacity = $this->positiveInteger($included?->getValue()) ?? 0;
        $maximumCapacity = $this->positiveInteger($maximum?->getValue());
        $unitPrice = $this->money($included?->getUnitPrice());
        $reason = $this->unavailableReason(
            plan: $plan,
            includedCapacity: $includedCapacity,
            maximumCapacity: $maximumCapacity,
            unitPrice: $unitPrice,
        );

        return new CapacityConfiguration(
            unit: 'beneficiary',
            unitPlural: 'beneficiaries',
            includedCapacity: $includedCapacity,
            maximumCapacity: $maximumCapacity,
            unitPrice: $unitPrice,
            purchasesEnabled: $reason === null,
            unavailableReason: $reason,
        );
    }

    private function businessConfiguration(Plan $plan): CapacityConfiguration
    {
        $includedCapacity = $plan->included_seats ?? 0;
        $maximumCapacity = 100000;
        $unitPrice = $this->money($plan->additional_seat_price);
        $reason = $this->unavailableReason(
            plan: $plan,
            includedCapacity: $includedCapacity,
            maximumCapacity: $maximumCapacity,
            unitPrice: $unitPrice,
        );

        return new CapacityConfiguration(
            unit: 'seat',
            unitPlural: 'seats',
            includedCapacity: $includedCapacity,
            maximumCapacity: $maximumCapacity,
            unitPrice: $unitPrice,
            purchasesEnabled: $reason === null,
            unavailableReason: $reason,
        );
    }

    private function unavailableReason(
        Plan $plan,
        int $includedCapacity,
        ?int $maximumCapacity,
        ?Money $unitPrice,
    ): ?string {
        if (! $plan->allows_capacity_purchases) {
            return 'Additional capacity purchases are disabled for this plan.';
        }

        if ($includedCapacity < 1) {
            return 'The included capacity has not been configured for this plan.';
        }

        if (! $unitPrice instanceof Money || $unitPrice->amountInMinorUnits === 0) {
            return 'The additional-capacity price has not been configured for this plan.';
        }

        if ($maximumCapacity !== null && $maximumCapacity <= $includedCapacity) {
            return 'This plan does not allow capacity above its included amount.';
        }

        return null;
    }

    private function prorate(
        Money $money,
        CarbonInterface $termStartsAt,
        CarbonInterface $termEndsAt,
        CarbonInterface $quotedAt,
    ): Money {
        $termSeconds = max(1, $termStartsAt->diffInSeconds($termEndsAt));
        $remainingSeconds = max(0, $quotedAt->diffInSeconds($termEndsAt));
        $proratedMinor = (int) round(
            $money->amountInMinorUnits * min(1, $remainingSeconds / $termSeconds),
            0,
            PHP_ROUND_HALF_UP,
        );

        return new Money(
            amountInMinorUnits: max(1, $proratedMinor),
            currency: $money->currency,
        );
    }

    private function money(string|int|null $amount): ?Money
    {
        if ($amount === null) {
            return null;
        }

        try {
            return Money::fromMajor($amount, $this->currency());
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    private function positiveInteger(?string $value): ?int
    {
        if ($value === null || ! ctype_digit($value) || (int) $value < 1) {
            return null;
        }

        return (int) $value;
    }

    private function featureAssignment(mixed $feature): ?FeaturePlan
    {
        if (! $feature instanceof Feature || ! $feature->relationLoaded('limits')) {
            return null;
        }

        $assignment = $feature->getRelation('limits');

        return $assignment instanceof FeaturePlan ? $assignment : null;
    }

    private function currency(): string
    {
        return config()->string('payments.currency', 'NGN');
    }
}
