<?php

use App\Enums\AccountTypes;
use App\Enums\CampaignStatus;
use App\Enums\WorkspaceBeneficiaries\WorkspaceBeneficiarySource;
use App\Enums\WorkspaceBeneficiaries\WorkspaceBeneficiaryStatus;
use App\Http\Resources\PatientConsultationSponsorshipResource;
use App\Models\Beneficiary;
use App\Models\Campaign;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Workspace;
use App\Models\WorkspaceBeneficiary;
use App\Queries\Consultations\PatientConsultationSponsorshipQuery;
use Carbon\CarbonInterface;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Revoltify\Subscriptionify\Enums\SubscriptionStatus;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $sqlite = config('database.connections.sqlite');
    $sqlite['database'] = ':memory:';

    config([
        'database.default' => 'mysql',
        'database.connections.mysql' => $sqlite,
    ]);
    DB::purge('mysql');

    Schema::connection('mysql')->create('users', function (Blueprint $table): void {
        $table->id();
        $table->string('email');
    });
    Schema::connection('mysql')->create('workspaces', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('type');
        $table->timestamps();
    });
    Schema::connection('mysql')->create('workspace_beneficiaries', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('workspace_id');
        $table->string('relatable_type');
        $table->unsignedBigInteger('relatable_id');
        $table->unsignedBigInteger('primary_sponsor_user_id')->nullable();
        $table->unsignedBigInteger('beneficiary_id')->nullable();
        $table->char('public_id', 26);
        $table->string('first_name');
        $table->string('last_name');
        $table->string('email');
        $table->string('phone')->default('');
        $table->string('status');
        $table->string('source');
        $table->unsignedInteger('invitation_version')->default(1);
        $table->timestamp('invited_at');
        $table->timestamp('expires_at');
        $table->timestamps();
    });
    Schema::connection('mysql')->create('plans', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('slug');
        $table->string('account_type');
        $table->decimal('price', 16, 2)->default(0);
        $table->boolean('is_free')->default(false);
        $table->boolean('is_active')->default(true);
        $table->unsignedInteger('trial_days')->default(0);
        $table->unsignedInteger('billing_period')->default(1);
        $table->string('billing_interval')->default('month');
        $table->unsignedInteger('grace_days')->default(0);
        $table->unsignedInteger('sort_order')->default(0);
        $table->boolean('allows_capacity_purchases')->default(false);
        $table->timestamps();
    });
    Schema::connection('mysql')->create('features', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('slug');
        $table->string('type');
        $table->unsignedInteger('sort_order')->default(0);
        $table->timestamps();
    });
    Schema::connection('mysql')->create('feature_plan', function (Blueprint $table): void {
        $table->foreignId('plan_id');
        $table->foreignId('feature_id');
        $table->string('value')->default('0');
        $table->decimal('unit_price', 16, 8)->default(0);
        $table->unsignedInteger('reset_period')->nullable();
        $table->string('reset_interval')->nullable();
    });
    Schema::connection('mysql')->create('subscriptions', function (Blueprint $table): void {
        $table->id();
        $table->string('subscribable_type');
        $table->unsignedBigInteger('subscribable_id');
        $table->foreignId('plan_id');
        $table->string('status');
        $table->timestamp('starts_at');
        $table->timestamp('ends_at')->nullable();
        $table->timestamp('trial_ends_at')->nullable();
        $table->unsignedInteger('capacity_count')->default(1);
        $table->boolean('auto_renew')->default(false);
        $table->unsignedInteger('renewal_attempts')->default(0);
        $table->timestamps();
    });
    Schema::connection('mysql')->create('campaigns', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('workspace_id');
        $table->string('name');
        $table->string('slug');
        $table->text('description')->nullable();
        $table->string('location')->nullable();
        $table->string('city')->nullable();
        $table->string('state')->nullable();
        $table->string('country')->nullable();
        $table->unsignedInteger('beneficiary_limit')->nullable();
        $table->string('currency')->default('NGN');
        $table->decimal('medication_budget', 16, 2)->default(0);
        $table->decimal('laboratory_budget', 16, 2)->default(0);
        $table->decimal('returned_amount', 16, 2)->default(0);
        $table->string('status');
        $table->timestamp('launched_at')->nullable();
        $table->timestamp('paused_at')->nullable();
        $table->timestamp('ended_at')->nullable();
        $table->date('start_date')->nullable();
        $table->date('end_date')->nullable();
        $table->timestamps();
    });
    Schema::connection('mysql')->create('campaign_consultation_quotas', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('campaign_id');
        $table->timestamps();
    });
    Schema::connection('mysql')->create('institutional_funding_programs', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('workspace_id');
        $table->timestamps();
    });
});

it('returns current plans and subscription active states', function (): void {
    $patient = sponsorshipPatient();
    $activeWorkspace = sponsorshipWorkspace(AccountTypes::BUSINESS, 'Active Business');
    $pastDueWorkspace = sponsorshipWorkspace(AccountTypes::INDIVIDUAL, 'Past Due Individual');
    $expiredWorkspace = sponsorshipWorkspace(AccountTypes::BUSINESS, 'Expired Business');
    $unsubscribedWorkspace = sponsorshipWorkspace(AccountTypes::INDIVIDUAL, 'No Plan Individual');

    foreach ([$activeWorkspace, $pastDueWorkspace, $expiredWorkspace, $unsubscribedWorkspace] as $workspace) {
        sponsorshipMembership($workspace, $patient);
    }

    $activePlan = sponsorshipPlan(AccountTypes::BUSINESS, 'Business Premium');
    $pastDuePlan = sponsorshipPlan(AccountTypes::INDIVIDUAL, 'Individual Premium');
    $expiredPlan = sponsorshipPlan(AccountTypes::BUSINESS, 'Business Basic');
    sponsorshipSubscription($activeWorkspace, $activePlan, SubscriptionStatus::Active, now()->addMonth());
    sponsorshipSubscription($pastDueWorkspace, $pastDuePlan, SubscriptionStatus::PastDue, now()->addMonth());
    sponsorshipSubscription($expiredWorkspace, $expiredPlan, SubscriptionStatus::Active, now()->subDay());

    $response = sponsorshipResponse($patient);
    $rows = collect($response)->keyBy('sponsor.name');

    expect($rows->get('Active Business'))->toMatchArray([
        'plan' => [
            'id' => $activePlan->getKey(),
            'name' => 'Business Premium',
            'slug' => 'business-premium',
        ],
        'is_active' => true,
    ])->and($rows->get('Past Due Individual'))->toMatchArray([
        'plan' => [
            'id' => $pastDuePlan->getKey(),
            'name' => 'Individual Premium',
            'slug' => 'individual-premium',
        ],
        'is_active' => false,
    ])->and($rows->get('Expired Business'))->toMatchArray([
        'plan' => [
            'id' => $expiredPlan->getKey(),
            'name' => 'Business Basic',
            'slug' => 'business-basic',
        ],
        'is_active' => false,
    ])->and($rows->get('No Plan Individual'))->toMatchArray([
        'plan' => null,
        'is_active' => false,
    ]);
});

it('returns active and inactive institutional campaign associations', function (): void {
    $patient = sponsorshipPatient();
    $workspace = sponsorshipWorkspace(AccountTypes::INSTITUTION, 'Institutional Sponsor');
    $active = sponsorshipCampaign($workspace, 'Active Campaign', CampaignStatus::IN_PROGRESS);
    $paused = sponsorshipCampaign($workspace, 'Paused Campaign', CampaignStatus::PAUSED);
    $upcoming = sponsorshipCampaign(
        $workspace,
        'Upcoming Campaign',
        CampaignStatus::PENDING,
        startsAt: now()->addWeek(),
    );
    $ended = sponsorshipCampaign($workspace, 'Ended Campaign', CampaignStatus::COMPLETED);

    foreach ([$active, $paused, $upcoming, $ended] as $campaign) {
        sponsorshipMembership($workspace, $patient, $campaign);
    }

    $rows = collect(sponsorshipResponse($patient))->keyBy('campaign.slug');

    expect($rows->get('active-campaign'))->toMatchArray([
        'plan' => null,
        'is_active' => true,
    ])->and($rows->get('paused-campaign'))->toMatchArray([
        'plan' => null,
        'is_active' => false,
    ])->and(data_get($rows->get('active-campaign'), 'limits.scheduled.reason'))->toBe('no_funding_program')
        ->and(data_get($rows->get('active-campaign'), 'limits.instant.reason'))->toBe('no_funding_program')
        ->and(data_get($rows->get('paused-campaign'), 'limits.scheduled.reason'))->toBe('no_active_campaign')
        ->and(data_get($rows->get('paused-campaign'), 'limits.instant.reason'))->toBe('no_active_campaign')
        ->and(data_get($rows->get('upcoming-campaign'), 'is_active'))->toBeFalse()
        ->and(data_get($rows->get('upcoming-campaign'), 'limits.scheduled.reason'))->toBe('no_active_campaign')
        ->and(data_get($rows->get('ended-campaign'), 'is_active'))->toBeFalse()
        ->and(data_get($rows->get('ended-campaign'), 'limits.scheduled.reason'))->toBe('no_active_campaign');
});

function sponsorshipPatient(): Beneficiary
{
    $patient = new Beneficiary;
    $patient->setRawAttributes([
        'id' => 700,
        'email' => 'patient@example.com',
        'type' => 'patient',
    ]);

    return $patient;
}

function sponsorshipWorkspace(AccountTypes $type, string $name): Workspace
{
    return Workspace::withoutEvents(fn (): Workspace => Workspace::query()->create([
        'name' => $name,
        'type' => $type,
    ]));
}

function sponsorshipPlan(AccountTypes $type, string $name): Plan
{
    return Plan::query()->create([
        'name' => $name,
        'slug' => Str::slug($name),
        'account_type' => $type,
    ]);
}

function sponsorshipSubscription(
    Workspace $workspace,
    Plan $plan,
    SubscriptionStatus $status,
    CarbonInterface $endsAt,
): Subscription {
    return Subscription::query()->create([
        'subscribable_type' => $workspace->getMorphClass(),
        'subscribable_id' => $workspace->getKey(),
        'plan_id' => $plan->getKey(),
        'status' => $status,
        'starts_at' => now()->subMonth(),
        'ends_at' => $endsAt,
    ]);
}

function sponsorshipCampaign(
    Workspace $workspace,
    string $name,
    CampaignStatus $status,
    ?CarbonInterface $startsAt = null,
): Campaign {
    return Campaign::query()->create([
        'workspace_id' => $workspace->getKey(),
        'name' => $name,
        'slug' => Str::slug($name),
        'status' => $status,
        'start_date' => $startsAt ?? now()->subWeek(),
        'end_date' => now()->addMonth(),
    ]);
}

function sponsorshipMembership(
    Workspace $workspace,
    Beneficiary $patient,
    ?Campaign $campaign = null,
): WorkspaceBeneficiary {
    $relatable = $campaign ?? $workspace;

    return WorkspaceBeneficiary::query()->create([
        'workspace_id' => $workspace->getKey(),
        'relatable_type' => $relatable->getMorphClass(),
        'relatable_id' => $relatable->getKey(),
        'beneficiary_id' => $patient->getKey(),
        'public_id' => (string) Str::ulid(),
        'first_name' => 'Eligible',
        'last_name' => 'Patient',
        'email' => 'patient@example.com',
        'status' => WorkspaceBeneficiaryStatus::Active,
        'source' => WorkspaceBeneficiarySource::Manual,
        'invited_at' => now()->subDay(),
        'expires_at' => now()->addYear(),
    ]);
}

/** @return list<array<string, mixed>> */
function sponsorshipResponse(Beneficiary $patient): array
{
    $resource = new PatientConsultationSponsorshipResource(
        app(PatientConsultationSponsorshipQuery::class)->getForPatient($patient),
    );

    return $resource->resolve(Request::create('/api/sponsor-eligibility', 'POST'));
}
