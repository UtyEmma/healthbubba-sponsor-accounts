<?php

use App\Enums\Account\Roles;
use App\Enums\AccountTypes;
use App\Enums\Transactions\TransactionFlow;
use App\Enums\Transactions\TransactionStatus;
use App\Enums\Transactions\TransactionTypes;
use App\Filament\RelationManagers\WalletRelationManager;
use App\Filament\Resources\Features\FeatureResource;
use App\Filament\Resources\Organizations\OrganizationResource;
use App\Filament\Resources\Plans\Pages\EditPlan;
use App\Filament\Resources\Plans\PlanResource;
use App\Filament\Resources\Plans\RelationManagers\FeaturesRelationManager;
use App\Filament\Resources\Subscriptions\SubscriptionResource;
use App\Filament\Resources\Transactions\TransactionResource;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\UserResource;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Transaction;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Revoltify\Subscriptionify\Enums\FeatureType;
use Revoltify\Subscriptionify\Enums\SubscriptionStatus as PackageSubscriptionStatus;
use Revoltify\Subscriptionify\Models\Feature;
use Revoltify\Subscriptionify\Models\Subscription;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('only allows administrators to access the admin panel', function () {
    $admin = User::factory()->create(['role' => Roles::ADMIN]);
    $user = User::factory()->create(['role' => Roles::USER]);
    $panel = Filament::getPanel('admin');

    expect($admin->canAccessPanel($panel))->toBeTrue()
        ->and($user->canAccessPanel($panel))->toBeFalse();
});

it('renders each admin resource list for a super administrator', function (string $resource) {
    $admin = User::factory()->create(['role' => Roles::SUPER_ADMIN]);

    actingAs($admin);

    get($resource::getUrl('index'))->assertSuccessful();
})->with([
    'plans' => PlanResource::class,
    'features' => FeatureResource::class,
    'subscriptions' => SubscriptionResource::class,
    'users' => UserResource::class,
    'organizations' => OrganizationResource::class,
    'transactions' => TransactionResource::class,
]);

it('renders each editable admin resource form for a super administrator', function (string $resource) {
    $admin = User::factory()->create(['role' => Roles::SUPER_ADMIN]);

    actingAs($admin);

    get($resource::getUrl('create'))->assertSuccessful();
})->with([
    'plan form' => PlanResource::class,
    'feature form' => FeatureResource::class,
    'subscription form' => SubscriptionResource::class,
    'user form' => UserResource::class,
    'organization form' => OrganizationResource::class,
]);

it('renders resource details and relationship managers', function () {
    $admin = User::factory()->create(['role' => Roles::SUPER_ADMIN]);
    $subscriber = User::factory()->create([
        'role' => Roles::USER,
        'type' => AccountTypes::INDIVIDUAL,
    ]);
    $organization = Organization::query()->create([
        'name' => 'Health Sponsor',
        'type' => AccountTypes::BUSINESS,
    ]);
    $plan = Plan::query()->create([
        'name' => 'Standard',
        'slug' => 'standard',
    ]);
    $feature = Feature::query()->create([
        'name' => 'Consultations',
        'slug' => 'consultations',
        'type' => FeatureType::Limit,
    ]);
    $plan->features()->attach($feature, [
        'value' => '10',
        'unit_price' => 0,
    ]);
    $subscription = Subscription::query()->create([
        'subscribable_type' => $subscriber->getMorphClass(),
        'subscribable_id' => $subscriber->getKey(),
        'plan_id' => $plan->getKey(),
        'status' => PackageSubscriptionStatus::Active,
        'starts_at' => now(),
    ]);
    $wallet = $subscriber->wallet()->firstOrFail();
    $transaction = Transaction::query()->forceCreate([
        'owner_type' => $subscriber->getMorphClass(),
        'owner_id' => $subscriber->getKey(),
        'transactable_type' => $wallet->getMorphClass(),
        'transactable_id' => $wallet->getKey(),
        'amount' => 2500,
        'reference' => 'TXN-ADMIN-TEST',
        'type' => TransactionTypes::TOPUP,
        'status' => TransactionStatus::COMPLETED,
        'flow' => TransactionFlow::CREDIT,
    ]);

    actingAs($admin);

    foreach ([
        PlanResource::getUrl('view', ['record' => $plan]),
        FeatureResource::getUrl('view', ['record' => $feature]),
        SubscriptionResource::getUrl('view', ['record' => $subscription]),
        UserResource::getUrl('view', ['record' => $subscriber]),
        OrganizationResource::getUrl('view', ['record' => $organization]),
        TransactionResource::getUrl('view', ['record' => $transaction]),
    ] as $url) {
        get($url)->assertSuccessful();
    }

    Livewire::test(WalletRelationManager::class, [
        'ownerRecord' => $subscriber,
        'pageClass' => EditUser::class,
    ])->assertOk();

    Livewire::test(FeaturesRelationManager::class, [
        'ownerRecord' => $plan,
        'pageClass' => EditPlan::class,
    ])->assertOk();
});
