<?php

use App\DTOs\CapacityPurchases\CapacityPurchaseSummary;
use App\Enums\AccountTypes;
use App\Enums\Subscriptions\Features;
use App\Http\Resources\CapacityPurchaseSummaryResource;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\Payments\CapacityPricingService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Revoltify\Subscriptionify\Models\Feature;
use Revoltify\Subscriptionify\Models\FeaturePlan;
use Tests\TestCase;

uses(TestCase::class);

it('charges the fixed additional-capacity price regardless of remaining term', function (
    AccountTypes $accountType,
    int $includedCapacity,
    string $unitPrice,
): void {
    $plan = new Plan([
        'account_type' => $accountType,
        'allows_capacity_purchases' => true,
        'included_seats' => $accountType === AccountTypes::BUSINESS ? $includedCapacity : null,
        'additional_seat_price' => $accountType === AccountTypes::BUSINESS ? $unitPrice : null,
    ]);

    if ($accountType === AccountTypes::INDIVIDUAL) {
        $included = new Feature(['slug' => Features::BENEFICIARIES_INCLUDED->value]);
        $included->setRelation('limits', new FeaturePlan([
            'value' => (string) $includedCapacity,
            'unit_price' => $unitPrice,
        ]));

        $maximum = new Feature(['slug' => Features::MAXIMUM_BENEFICIARIES->value]);
        $maximum->setRelation('limits', new FeaturePlan([
            'value' => (string) ($includedCapacity + 10),
            'unit_price' => '0',
        ]));

        $plan->setRelation('features', collect([$included, $maximum]));
    }

    $startsAt = CarbonImmutable::parse('2026-11-01 00:00:00');
    $endsAt = $startsAt->addMonth();
    $subscription = new Subscription([
        'capacity_count' => $includedCapacity,
        'starts_at' => $startsAt,
        'ends_at' => $endsAt,
    ]);
    $subscription->setRelation('plan', $plan);

    $pricing = new CapacityPricingService;
    $earlyQuote = $pricing->quote($subscription, 2, $startsAt->addDay());
    $lateQuote = $pricing->quote($subscription, 2, $endsAt->subDay());
    $expectedUnitMinor = (int) $unitPrice * 100;

    expect($earlyQuote->unitPrice->amountInMinorUnits)
        ->toBe($expectedUnitMinor)
        ->and($earlyQuote->total->amountInMinorUnits)
        ->toBe($expectedUnitMinor * 2)
        ->and($lateQuote->unitPrice->amountInMinorUnits)
        ->toBe($expectedUnitMinor)
        ->and($lateQuote->total->amountInMinorUnits)
        ->toBe($expectedUnitMinor * 2)
        ->and($lateQuote->renewalIncrease->amountInMinorUnits)
        ->toBe($expectedUnitMinor * 2);
})->with([
    'individual beneficiary slots' => [AccountTypes::INDIVIDUAL, 3, '5000'],
    'business employee seats' => [AccountTypes::BUSINESS, 5, '10500'],
]);

it('exposes the fixed capacity unit price without a prorated amount', function (): void {
    $summary = new CapacityPurchaseSummary(
        subscriptionId: 10,
        unit: 'beneficiary',
        unitPlural: 'beneficiaries',
        currentCapacity: 3,
        includedCapacity: 3,
        maximumCapacity: 6,
        unitPrice: '5000.00',
        currency: 'NGN',
        walletBalance: '20000.00',
        available: true,
        unavailableReason: null,
        termEndsAt: '2026-12-01T00:00:00.000000Z',
    );

    $payload = (new CapacityPurchaseSummaryResource($summary))
        ->resolve(Request::create('/billing'));

    expect($payload)->toBe([
        'subscription_id' => 10,
        'unit' => 'beneficiary',
        'unit_plural' => 'beneficiaries',
        'current_capacity' => 3,
        'included_capacity' => 3,
        'maximum_capacity' => 6,
        'unit_price' => '5000.00',
        'currency' => 'NGN',
        'wallet_balance' => '20000.00',
        'available' => true,
        'unavailable_reason' => null,
        'term_ends_at' => '2026-12-01T00:00:00.000000Z',
    ])->and(array_key_exists('prorated_unit_price', $payload))->toBeFalse();
});
