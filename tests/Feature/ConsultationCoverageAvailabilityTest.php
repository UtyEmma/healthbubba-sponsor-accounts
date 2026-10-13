<?php

use App\Enums\AccountTypes;
use App\Enums\Subscriptions\Features;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Workspace;
use App\Services\Consultations\ConsultationCoverageService;
use Database\Seeders\PlanFeatureSeeder;
use Revoltify\Subscriptionify\Enums\SubscriptionStatus;
use Revoltify\Subscriptionify\Models\Feature;

use function Pest\Laravel\seed;

it('marks consultation types omitted from an individual plan as unavailable', function (): void {
    $coverage = consultationCoverageForPlan('individual-basic', AccountTypes::INDIVIDUAL);
    $scheduled = consultationAllocation($coverage, 'scheduled');
    $firstStep = $coverage['scaling']['steps'][0];

    expect($scheduled['unavailableReason'])->not->toBeNull()
        ->and($firstStep['scheduled'])->toMatchArray([
            'available' => false,
            'base' => null,
            'additional' => null,
            'total' => null,
        ])
        ->and($firstStep['instant'])->toMatchArray([
            'available' => true,
            'base' => 9,
            'additional' => 0,
            'total' => 9,
        ]);
});

it('returns finite scaling totals when both consultation types are included', function (): void {
    $coverage = consultationCoverageForPlan('individual-premium', AccountTypes::INDIVIDUAL);
    $firstStep = $coverage['scaling']['steps'][0];

    expect($firstStep['scheduled'])->toMatchArray([
        'available' => true,
        'base' => 3,
        'total' => 3,
    ])->and($firstStep['instant'])->toMatchArray([
        'available' => true,
        'base' => 18,
        'total' => 18,
    ]);
});

it('preserves an explicitly unlimited consultation feature', function (): void {
    seed(PlanFeatureSeeder::class);

    $plan = Plan::query()
        ->with('features')
        ->where('slug', 'individual-premium')
        ->firstOrFail();
    $scheduled = $plan->features->first(
        static fn (Feature $feature): bool => $feature->getRawOriginal('slug') === Features::SCHEDULED_CONSULTATIONS->value,
    );

    if (! $scheduled instanceof Feature) {
        throw new RuntimeException('The scheduled consultation feature was not seeded.');
    }

    $plan->features()->updateExistingPivot($scheduled->getKey(), ['value' => '0']);

    $coverage = consultationCoverage($plan, AccountTypes::INDIVIDUAL);
    $firstStep = $coverage['scaling']['steps'][0];

    expect($firstStep['scheduled'])->toMatchArray([
        'available' => true,
        'base' => null,
        'additional' => null,
        'total' => null,
    ]);
});

it('marks a consultation type omitted from a business plan as unavailable', function (): void {
    $coverage = consultationCoverageForPlan('business-basic', AccountTypes::BUSINESS);
    $firstStep = $coverage['scaling']['steps'][0];

    expect($firstStep['scheduled']['available'])->toBeFalse()
        ->and($firstStep['scheduled']['total'])->toBeNull()
        ->and($firstStep['instant'])->toMatchArray([
            'available' => true,
            'base' => 2,
            'total' => 2,
        ]);
});

/** @return array<string, mixed> */
function consultationCoverageForPlan(string $planSlug, AccountTypes $accountType): array
{
    seed(PlanFeatureSeeder::class);

    $plan = Plan::query()->where('slug', $planSlug)->firstOrFail();

    return consultationCoverage($plan, $accountType);
}

/** @return array<string, mixed> */
function consultationCoverage(Plan $plan, AccountTypes $accountType): array
{
    $workspace = Workspace::withoutEvents(fn (): Workspace => Workspace::query()->create([
        'name' => "{$plan->name} Workspace",
        'type' => $accountType,
    ]));

    Subscription::query()->create([
        'subscribable_type' => $workspace->getMorphClass(),
        'subscribable_id' => $workspace->getKey(),
        'plan_id' => $plan->getKey(),
        'capacity_count' => $accountType === AccountTypes::INDIVIDUAL ? 3 : 1,
        'status' => SubscriptionStatus::Active,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addMonth(),
    ]);

    return app(ConsultationCoverageService::class)->summary($workspace);
}

/**
 * @param  array<string, mixed>  $coverage
 * @return array<string, mixed>
 */
function consultationAllocation(array $coverage, string $type): array
{
    $allocations = $coverage['allocations'] ?? null;

    if (! is_array($allocations)) {
        throw new RuntimeException('Consultation allocations were not returned.');
    }

    foreach ($allocations as $allocation) {
        if (is_array($allocation) && ($allocation['type'] ?? null) === $type) {
            return $allocation;
        }
    }

    throw new RuntimeException("The {$type} consultation allocation was not returned.");
}
