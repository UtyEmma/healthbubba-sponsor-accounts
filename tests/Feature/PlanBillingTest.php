<?php

use App\Enums\AccountTypes;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use Database\Seeders\PlanFeatureSeeder;
use Illuminate\Database\Eloquent\Model;
use Inertia\Testing\AssertableInertia as Assert;
use Revoltify\Subscriptionify\Enums\SubscriptionStatus;
use Revoltify\Subscriptionify\Models\Subscription;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

it('shows only plans and subscription details for the current account type', function (
    AccountTypes $accountType,
    string $routeName,
    string $component,
    array $expectedPlanSlugs,
) {
    seed(PlanFeatureSeeder::class);

    $user = User::factory()->create(['type' => $accountType]);
    $subscriber = $user;

    if ($accountType !== AccountTypes::INDIVIDUAL) {
        $organization = Organization::query()->create([
            'name' => "{$accountType->label()} Account",
            'type' => $accountType,
        ]);

        $user->organizations()->attach($organization, [
            'role' => 'owner',
            'status' => 'active',
        ]);

        $subscriber = $organization;
    }

    expect($subscriber)->toBeInstanceOf(Model::class);

    $plan = Plan::query()
        ->forAccountType($accountType)
        ->orderBy('sort_order')
        ->firstOrFail();

    Subscription::query()->create([
        'subscribable_type' => $subscriber->getMorphClass(),
        'subscribable_id' => $subscriber->getKey(),
        'plan_id' => $plan->getKey(),
        'status' => SubscriptionStatus::Active,
        'starts_at' => now()->subMonth(),
        'ends_at' => now()->addMonth(),
    ]);

    actingAs($user)
        ->get(route($routeName))
        ->assertSuccessful()
        ->assertInertia(function (Assert $page) use (
            $accountType,
            $component,
            $expectedPlanSlugs,
            $plan,
        ): void {
            $page
                ->component($component)
                ->where('accountType', $accountType->value)
                ->where('accountTypeLabel', $accountType->label())
                ->has('plans', count($expectedPlanSlugs))
                ->where('subscription.status', SubscriptionStatus::Active->value)
                ->where('subscription.isValid', true)
                ->where('subscription.plan.id', $plan->getKey());

            foreach ($expectedPlanSlugs as $index => $slug) {
                $page->where("plans.{$index}.slug", $slug);
            }
        });
})->with([
    'individual sponsor' => [
        AccountTypes::INDIVIDUAL,
        'plans.index',
        'sponsor/plan-and-billing/index',
        ['individual-basic', 'individual-premium', 'individual-coordinated-care'],
    ],
    'business sponsor organization' => [
        AccountTypes::BUSINESS,
        'business.plans',
        'business-sponsor/plan-and-seats/index',
        ['business-basic', 'business-premium'],
    ],
    'institutional sponsor organization' => [
        AccountTypes::INSTITUTION,
        'plans.index',
        'sponsor/plan-and-billing/index',
        ['institution-community-health-program-2026'],
    ],
]);

it('shows an empty subscription state without selecting another account type plan', function () {
    seed(PlanFeatureSeeder::class);

    $user = User::factory()->create(['type' => AccountTypes::INDIVIDUAL]);

    actingAs($user)
        ->get(route('plans.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('sponsor/plan-and-billing/index')
            ->where('subscription', null)
            ->has('plans', 3)
            ->where('plans.0.slug', 'individual-basic')
            ->missing('plans.3'));
});
