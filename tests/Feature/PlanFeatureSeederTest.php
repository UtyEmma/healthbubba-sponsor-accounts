<?php

use App\Enums\AccountTypes;
use App\Enums\Subscriptions\Features;
use App\Models\Plan;
use App\Models\PlanIntervalPrice;
use Database\Seeders\PlanFeatureSeeder;
use Revoltify\Subscriptionify\Enums\Interval;
use Revoltify\Subscriptionify\Models\Feature;
use Revoltify\Subscriptionify\Models\FeaturePlan;

use function Pest\Laravel\seed;

it('seeds account-specific plans and their UI feature allowances idempotently', function () {
    seed(PlanFeatureSeeder::class);

    expect(Plan::query()->count())->toBe(6)
        ->and(Feature::query()->count())->toBe(26)
        ->and(Feature::query()->orderBy('slug')->pluck('slug')->all())
        ->toBe(collect(Features::cases())->pluck('value')->sort()->values()->all())
        ->and(FeaturePlan::query()->count())->toBe(47)
        ->and(Plan::query()->whereNull('account_type')->doesntExist())->toBeTrue()
        ->and(Plan::query()->whereNotIn('account_type', array_column(AccountTypes::cases(), 'value'))->doesntExist())->toBeTrue()
        ->and(Plan::query()->forAccountType(AccountTypes::INDIVIDUAL)->count())->toBe(3)
        ->and(Plan::query()->forAccountType(AccountTypes::BUSINESS)->count())->toBe(2)
        ->and(Plan::query()->forAccountType(AccountTypes::INSTITUTION)->count())->toBe(1)
        ->and(PlanIntervalPrice::query()->count())->toBe(9);

    $individualPremium = Plan::query()
        ->with('features')
        ->where('slug', 'individual-premium')
        ->firstOrFail();
    $individualScheduled = $individualPremium->features->firstWhere('slug', Features::SCHEDULED_CONSULTATIONS->value);

    expect($individualPremium->account_type)->toBe(AccountTypes::INDIVIDUAL)
        ->and($individualPremium->price)->toBe('39000.00')
        ->and($individualScheduled?->pivot?->getValue())->toBe('3')
        ->and($individualScheduled?->pivot?->getResetInterval())->toBe(Interval::Month);

    $businessPremium = Plan::query()
        ->with('features')
        ->where('slug', 'business-premium')
        ->firstOrFail();
    $businessScheduled = $businessPremium->features->firstWhere('slug', Features::SCHEDULED_CONSULTATIONS_PER_SEAT->value);

    expect($businessPremium->account_type)->toBe(AccountTypes::BUSINESS)
        ->and($businessPremium->price)->toBe('10500.00')
        ->and($businessScheduled?->pivot?->getValue())->toBe('1');

    $institutionalPlan = Plan::query()
        ->with('features')
        ->where('slug', 'institution-community-health-program-2026')
        ->firstOrFail();
    $institutionalScheduled = $institutionalPlan->features->firstWhere('slug', Features::SCHEDULED_CONSULTATIONS->value);

    expect($institutionalPlan->account_type)->toBe(AccountTypes::INSTITUTION)
        ->and($institutionalPlan->price)->toBe('25000000.00')
        ->and($institutionalPlan->billing_interval)->toBe(Interval::Year)
        ->and($institutionalScheduled?->pivot?->getValue())->toBe('2000')
        ->and($institutionalScheduled?->pivot?->getResetInterval())->toBe(Interval::Year);

    seed(PlanFeatureSeeder::class);

    expect(Plan::query()->count())->toBe(6)
        ->and(Feature::query()->count())->toBe(26)
        ->and(FeaturePlan::query()->count())->toBe(47)
        ->and(PlanIntervalPrice::query()->count())->toBe(9);
});
