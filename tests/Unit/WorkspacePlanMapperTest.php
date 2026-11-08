<?php

use App\Enums\AccountTypes;
use App\Enums\Subscriptions\Features;
use App\Mappers\WorkspacePlanMapper;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Revoltify\Subscriptionify\Enums\FeatureType;
use Revoltify\Subscriptionify\Enums\SubscriptionStatus;
use Revoltify\Subscriptionify\Models\Feature;
use Revoltify\Subscriptionify\Models\FeaturePlan;
use Tests\TestCase;

uses(TestCase::class);

it('places unavailable toggle and limited features at the bottom of their lists', function (): void {
    $maximumBeneficiaries = billingFeature(
        Features::MAXIMUM_BENEFICIARIES,
        FeatureType::Limit,
        1,
    );
    $scheduledConsultations = billingFeature(
        Features::SCHEDULED_CONSULTATIONS,
        FeatureType::Consumable,
        2,
        '9',
    );
    $prioritySupport = billingFeature(
        Features::PRIORITY_SUPPORT,
        FeatureType::Toggle,
        3,
    );
    $sharedConsultationPool = billingFeature(
        Features::SHARED_CONSULTATION_POOL,
        FeatureType::Toggle,
        4,
        '1',
    );
    $instantConsultations = billingFeature(
        Features::INSTANT_CONSULTATIONS,
        FeatureType::Consumable,
        5,
        '3',
    );
    $includedBeneficiaries = billingFeature(
        Features::BENEFICIARIES_INCLUDED,
        FeatureType::Limit,
        6,
    );
    $chronicCareSupport = billingFeature(
        Features::CHRONIC_DISEASE_MONITORING,
        FeatureType::Toggle,
        7,
    );
    $followUpTracking = billingFeature(
        Features::FOLLOW_UP_TRACKING,
        FeatureType::Toggle,
        8,
        '1',
    );

    $plan = new Plan([
        'name' => 'Premium',
        'slug' => 'premium',
        'account_type' => AccountTypes::BUSINESS,
    ]);
    $plan->setRelation('features', new EloquentCollection([
        $scheduledConsultations,
        $sharedConsultationPool,
        $instantConsultations,
        $followUpTracking,
    ]));

    $catalog = collect([
        $maximumBeneficiaries,
        $scheduledConsultations,
        $prioritySupport,
        $sharedConsultationPool,
        $instantConsultations,
        $includedBeneficiaries,
        $chronicCareSupport,
        $followUpTracking,
    ]);

    $method = new ReflectionMethod(WorkspacePlanMapper::class, 'mapFeatureCatalog');
    $mapped = $method->invoke(app(WorkspacePlanMapper::class), $plan, $catalog);
    $quotaDescriptions = array_column($mapped['quotas'], 'description');

    expect(array_column($mapped['quotas'], 'slug'))->toBe([
        Features::SCHEDULED_CONSULTATIONS->value,
        Features::INSTANT_CONSULTATIONS->value,
        Features::MAXIMUM_BENEFICIARIES->value,
        Features::BENEFICIARIES_INCLUDED->value,
    ])->and(array_slice($quotaDescriptions, -2))->toBe([
        'Not Included',
        'Not Included',
    ])->and(array_column($mapped['features'], 'slug'))->toBe([
        Features::SHARED_CONSULTATION_POOL->value,
        Features::FOLLOW_UP_TRACKING->value,
        Features::PRIORITY_SUPPORT->value,
        Features::CHRONIC_DISEASE_MONITORING->value,
    ])->and(array_column($mapped['features'], 'included'))->toBe([
        true,
        true,
        false,
        false,
    ]);
});

it('does not expose an active subscription warning for unavailable checkout', function (): void {
    $workspace = new Workspace(['type' => AccountTypes::INDIVIDUAL]);
    $plan = new Plan(['account_type' => AccountTypes::INDIVIDUAL]);
    $subscription = new Subscription(['status' => SubscriptionStatus::Active]);

    $method = new ReflectionMethod(WorkspacePlanMapper::class, 'checkoutState');
    $state = $method->invoke(
        app(WorkspacePlanMapper::class),
        $workspace,
        $plan,
        false,
        $subscription,
    );

    expect($state)->toBe([
        'available' => false,
        'reason' => null,
    ]);
});

function billingFeature(
    Features $feature,
    FeatureType $type,
    int $sortOrder,
    ?string $quota = null,
): Feature {
    $model = new Feature([
        'name' => $feature->label(),
        'slug' => $feature->value,
        'type' => $type,
        'sort_order' => $sortOrder,
    ]);

    if ($quota !== null) {
        $model->setRelation('limits', new FeaturePlan([
            'value' => $quota,
            'unit_price' => '0',
        ]));
    }

    return $model;
}
