<?php

use App\Enums\AccountTypes;
use App\Enums\Subscriptions\FeatureSlug;
use App\Models\Plan;
use Database\Seeders\PlanFeatureSeeder;
use Revoltify\Subscriptionify\Enums\Interval;
use Revoltify\Subscriptionify\Models\Feature;
use Revoltify\Subscriptionify\Models\FeaturePlan;

use function Pest\Laravel\seed;

it('seeds account-specific plans and their UI feature allowances idempotently', function () {
    seed(PlanFeatureSeeder::class);

    expect(Plan::query()->count())->toBe(6)
        ->and(Feature::query()->count())->toBe(23)
        ->and(Feature::query()->orderBy('slug')->pluck('slug')->all())
        ->toBe(collect(FeatureSlug::cases())->pluck('value')->sort()->values()->all())
        ->and(FeaturePlan::query()->count())->toBe(50)
        ->and(Plan::query()->whereNull('account_type')->doesntExist())->toBeTrue()
        ->and(Plan::query()->whereNotIn('account_type', array_column(AccountTypes::cases(), 'value'))->doesntExist())->toBeTrue()
        ->and(Plan::query()->forAccountType(AccountTypes::INDIVIDUAL)->count())->toBe(3)
        ->and(Plan::query()->forAccountType(AccountTypes::BUSINESS)->count())->toBe(2)
        ->and(Plan::query()->forAccountType(AccountTypes::INSTITUTION)->count())->toBe(1);

    $individualPremium = Plan::query()
        ->with('features')
        ->where('slug', 'individual-premium')
        ->firstOrFail();
    $individualGp = $individualPremium->features->firstWhere('slug', FeatureSlug::GP_CONSULTATIONS->value);

    expect($individualPremium->account_type)->toBe(AccountTypes::INDIVIDUAL)
        ->and($individualPremium->price)->toBe('33000.00')
        ->and($individualGp?->pivot?->getValue())->toBe('10')
        ->and($individualGp?->pivot?->getResetInterval())->toBe(Interval::Month);

    $businessPremium = Plan::query()
        ->with('features')
        ->where('slug', 'business-premium')
        ->firstOrFail();
    $businessGp = $businessPremium->features->firstWhere('slug', FeatureSlug::GP_CONSULTATIONS_PER_SEAT->value);

    expect($businessPremium->account_type)->toBe(AccountTypes::BUSINESS)
        ->and($businessPremium->price)->toBe('10500.00')
        ->and($businessGp?->pivot?->getValue())->toBe('3');

    $institutionalPlan = Plan::query()
        ->with('features')
        ->where('slug', 'institution-community-health-program-2026')
        ->firstOrFail();
    $institutionalGp = $institutionalPlan->features->firstWhere('slug', FeatureSlug::GP_CONSULTATIONS->value);

    expect($institutionalPlan->account_type)->toBe(AccountTypes::INSTITUTION)
        ->and($institutionalPlan->price)->toBe('25000000.00')
        ->and($institutionalPlan->billing_interval)->toBe(Interval::Year)
        ->and($institutionalGp?->pivot?->getValue())->toBe('2000')
        ->and($institutionalGp?->pivot?->getResetInterval())->toBe(Interval::Year);

    seed(PlanFeatureSeeder::class);

    expect(Plan::query()->count())->toBe(6)
        ->and(Feature::query()->count())->toBe(23)
        ->and(FeaturePlan::query()->count())->toBe(50);
});
