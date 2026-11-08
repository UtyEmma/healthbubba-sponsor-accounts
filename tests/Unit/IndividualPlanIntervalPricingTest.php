<?php

use App\Enums\AccountTypes;
use App\Enums\Subscriptions\Features;
use App\Enums\Subscriptions\IndividualPlanBillingCycle;
use App\Exceptions\Payments\CheckoutUnavailable;
use App\Models\Plan;
use App\Models\PlanIntervalPrice;
use App\Models\Subscription;
use App\Services\Payments\BillingCycleUpgradePricingService;
use App\Services\Payments\IndividualPlanIntervalPricingService;
use App\Services\Payments\PlanPricingService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Revoltify\Subscriptionify\Enums\SubscriptionStatus;
use Revoltify\Subscriptionify\Models\Feature;
use Revoltify\Subscriptionify\Models\FeaturePlan;
use Tests\TestCase;

uses(TestCase::class);

it('resolves every individual interval price and rounded saving', function (
    string $monthlyPrice,
    IndividualPlanBillingCycle $cycle,
    string $expectedPrice,
    int $expectedSaving,
): void {
    $plan = individualPlan($monthlyPrice, [
        'quarterly' => match ($monthlyPrice) {
            '20000.00' => '57000.00',
            '39000.00' => '111150.00',
            default => '168150.00',
        },
        'biannual' => match ($monthlyPrice) {
            '20000.00' => '108000.00',
            '39000.00' => '210600.00',
            default => '318600.00',
        },
        'annual' => match ($monthlyPrice) {
            '20000.00' => '204000.00',
            '39000.00' => '399000.00',
            default => '600000.00',
        },
    ]);
    $option = app(IndividualPlanIntervalPricingService::class)
        ->options($plan)
        ->first(fn ($option): bool => $option->cycle === $cycle);

    expect($option)->not->toBeNull()
        ->and($option->price->toMajorAmount())->toBe($expectedPrice)
        ->and($option->savingsPercentage)->toBe($expectedSaving)
        ->and($option->available)->toBeTrue();
})->with([
    ['20000.00', IndividualPlanBillingCycle::MONTHLY, '20000.00', 0],
    ['20000.00', IndividualPlanBillingCycle::QUARTERLY, '57000.00', 5],
    ['20000.00', IndividualPlanBillingCycle::BIANNUAL, '108000.00', 10],
    ['20000.00', IndividualPlanBillingCycle::ANNUAL, '204000.00', 15],
    ['39000.00', IndividualPlanBillingCycle::MONTHLY, '39000.00', 0],
    ['39000.00', IndividualPlanBillingCycle::QUARTERLY, '111150.00', 5],
    ['39000.00', IndividualPlanBillingCycle::BIANNUAL, '210600.00', 10],
    ['39000.00', IndividualPlanBillingCycle::ANNUAL, '399000.00', 15],
    ['59000.00', IndividualPlanBillingCycle::MONTHLY, '59000.00', 0],
    ['59000.00', IndividualPlanBillingCycle::QUARTERLY, '168150.00', 5],
    ['59000.00', IndividualPlanBillingCycle::BIANNUAL, '318600.00', 10],
    ['59000.00', IndividualPlanBillingCycle::ANNUAL, '600000.00', 15],
]);

it('charges additional individual beneficiaries at the full cycle multiplier', function (
    IndividualPlanBillingCycle $cycle,
    string $expectedTotal,
): void {
    $plan = individualPlan('20000.00', [
        'quarterly' => '57000.00',
        'biannual' => '108000.00',
        'annual' => '204000.00',
    ], withCapacity: true);
    $charge = app(PlanPricingService::class)->checkout($plan, 2, $cycle);

    expect($charge->money->toMajorAmount())->toBe($expectedTotal)
        ->and($charge->additionalCapacity)->toBe(2);
})->with([
    [IndividualPlanBillingCycle::MONTHLY, '30000.00'],
    [IndividualPlanBillingCycle::QUARTERLY, '87000.00'],
    [IndividualPlanBillingCycle::BIANNUAL, '168000.00'],
    [IndividualPlanBillingCycle::ANNUAL, '324000.00'],
]);

it('charges the full period difference for an immediate billing cycle upgrade', function (): void {
    $plan = individualPlan('20000.00', [
        'quarterly' => '57000.00',
        'biannual' => '108000.00',
        'annual' => '204000.00',
    ], withCapacity: true);
    $startsAt = CarbonImmutable::parse('2026-11-01 00:00:00');
    $subscription = new Subscription([
        'status' => SubscriptionStatus::Active,
        'billing_cycle' => IndividualPlanBillingCycle::MONTHLY,
        'capacity_count' => 5,
        'starts_at' => $startsAt,
        'ends_at' => $startsAt->addMonth(),
    ]);
    $subscription->setRelation('plan', $plan);

    $quote = app(BillingCycleUpgradePricingService::class)->quote(
        $subscription,
        IndividualPlanBillingCycle::QUARTERLY,
        CarbonImmutable::parse('2026-11-08 12:00:00'),
    );

    expect($quote->currentAmount->toMajorAmount())->toBe('30000.00')
        ->and($quote->targetAmount->toMajorAmount())->toBe('87000.00')
        ->and($quote->amountDueNow->toMajorAmount())->toBe('57000.00')
        ->and($quote->targetEndsAt->toDateString())->toBe('2027-02-01');
});

it('rejects a billing cycle downgrade', function (): void {
    $plan = individualPlan('20000.00', [
        'quarterly' => '57000.00',
        'biannual' => '108000.00',
        'annual' => '204000.00',
    ]);
    $startsAt = CarbonImmutable::parse('2026-11-01 00:00:00');
    $subscription = new Subscription([
        'status' => SubscriptionStatus::Active,
        'billing_cycle' => IndividualPlanBillingCycle::QUARTERLY,
        'capacity_count' => 1,
        'starts_at' => $startsAt,
        'ends_at' => $startsAt->addMonths(3),
    ]);
    $subscription->setRelation('plan', $plan);

    expect(fn () => app(BillingCycleUpgradePricingService::class)->quote(
        $subscription,
        IndividualPlanBillingCycle::MONTHLY,
    ))->toThrow(
        CheckoutUnavailable::class,
        'Billing-cycle downgrades are not available. Cancel the current subscription and purchase a new subscription with the shorter billing cycle.',
    );
});

/** @param array<string, string> $prices */
function individualPlan(string $monthlyPrice, array $prices, bool $withCapacity = false): Plan
{
    $plan = new Plan([
        'account_type' => AccountTypes::INDIVIDUAL,
        'price' => $monthlyPrice,
        'is_active' => true,
        'is_free' => false,
        'trial_days' => 0,
        'allows_capacity_purchases' => $withCapacity,
    ]);
    $plan->setRelation('intervalPrices', new Collection(collect($prices)
        ->map(fn (string $price, string $cycle): PlanIntervalPrice => new PlanIntervalPrice([
            'billing_cycle' => $cycle,
            'price' => $price,
            'is_active' => true,
        ]))
        ->values()
        ->all()));

    if ($withCapacity) {
        $included = new Feature(['slug' => Features::BENEFICIARIES_INCLUDED->value]);
        $included->setRelation('limits', new FeaturePlan([
            'value' => '3',
            'unit_price' => '5000',
        ]));
        $maximum = new Feature(['slug' => Features::MAXIMUM_BENEFICIARIES->value]);
        $maximum->setRelation('limits', new FeaturePlan([
            'value' => '6',
            'unit_price' => '0',
        ]));
        $plan->setRelation('features', new Collection([$included, $maximum]));
    }

    return $plan;
}
