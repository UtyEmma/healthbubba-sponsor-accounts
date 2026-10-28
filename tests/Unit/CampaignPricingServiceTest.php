<?php

use App\Actions\Campaigns\CreateCampaignAction;
use App\Actions\Campaigns\SaveCampaignPricingTierAction;
use App\DTOs\Campaigns\CampaignBoothData;
use App\DTOs\Campaigns\CampaignDetailsData;
use App\DTOs\Campaigns\CampaignEnrollmentData;
use App\DTOs\Campaigns\CampaignHealthcareAllocationData;
use App\DTOs\Campaigns\CreateCampaignData;
use App\DTOs\Campaigns\SaveCampaignPricingTierData;
use App\Enums\Account\Roles;
use App\Enums\AccountTypes;
use App\Enums\CampaignEnrollmentMethod;
use App\Enums\CampaignRecurringCostCategory;
use App\Enums\Transactions\TransactionTypes;
use App\Models\CampaignPricingTier;
use App\Models\CampaignRecurringCost;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Campaigns\CampaignPricingService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $sqlite = config('database.connections.sqlite');
    $sqlite['database'] = ':memory:';

    config([
        'database.default' => 'sqlite',
        'database.connections.sqlite' => $sqlite,
    ]);
    DB::purge('sqlite');

    Schema::create('campaign_pricing_tiers', function (Blueprint $table): void {
        $table->id();
        $table->unsignedInteger('minimum_beneficiaries');
        $table->unsignedInteger('maximum_beneficiaries')->nullable();
        $table->decimal('beneficiary_unit_fee', 16, 2);
        $table->decimal('monthly_service_fee', 16, 2)->nullable();
        $table->char('currency', 3);
        $table->boolean('is_active');
        $table->unsignedInteger('sort_order');
        $table->unsignedBigInteger('updated_by_user_id')->nullable();
        $table->timestamps();
    });
    Schema::create('workspaces', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('type');
        $table->timestamp('onboarded_at')->nullable();
        $table->timestamps();
    });
    Schema::create('wallets', function (Blueprint $table): void {
        $table->id();
        $table->string('owner_type');
        $table->unsignedBigInteger('owner_id');
        $table->decimal('balance', 16, 2)->default(0);
        $table->char('currency', 3)->default('NGN');
        $table->timestamps();
    });
    Schema::create('campaigns', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('workspace_id');
        $table->string('name');
        $table->string('slug')->unique();
        $table->text('description')->nullable();
        $table->string('location')->nullable();
        $table->string('enrollment_method')->nullable();
        $table->unsignedInteger('estimated_beneficiaries')->nullable();
        $table->unsignedBigInteger('campaign_pricing_tier_id')->nullable();
        $table->decimal('beneficiary_unit_fee', 16, 2)->nullable();
        $table->decimal('beneficiary_setup_fee', 16, 2)->nullable();
        $table->decimal('monthly_service_fee', 16, 2)->nullable();
        $table->unsignedInteger('beneficiary_limit')->nullable();
        $table->date('start_date');
        $table->date('end_date');
        $table->string('status');
        $table->decimal('scheduled_consultation_fee', 16, 2)->nullable();
        $table->decimal('instant_consultation_fee', 16, 2)->nullable();
        $table->char('currency', 3)->default('NGN');
        $table->decimal('medication_budget', 16, 2)->default(0);
        $table->decimal('laboratory_budget', 16, 2)->default(0);
        $table->decimal('returned_amount', 16, 2)->default(0);
        $table->string('allocation_reference')->nullable();
        $table->string('display_enrollment_code')->nullable();
        $table->timestamp('launched_at')->nullable();
        $table->boolean('booth_required')->default(false);
        $table->unsignedInteger('booth_count')->nullable();
        $table->date('booth_preferred_deployment_date')->nullable();
        $table->string('booth_site')->nullable();
        $table->string('booth_contact_name')->nullable();
        $table->string('booth_contact_phone')->nullable();
        $table->decimal('booth_setup_unit_fee', 16, 2)->nullable();
        $table->decimal('booth_monthly_unit_fee', 16, 2)->nullable();
        $table->string('coverage_type_override')->nullable();
        $table->unsignedInteger('scheduled_consultation_limit_per_beneficiary_override')->nullable();
        $table->unsignedInteger('instant_consultation_limit_per_beneficiary_override')->nullable();
        $table->unsignedInteger('daily_consultation_limit_override')->nullable();
        $table->string('coverage_expiry_override')->nullable();
        $table->string('payment_preference_override')->nullable();
        $table->timestamps();
    });
    Schema::create('campaign_enrollment_codes', function (Blueprint $table): void {
        $table->id();
        $table->string('public_id');
        $table->unsignedBigInteger('campaign_id');
        $table->unsignedBigInteger('created_by_user_id')->nullable();
        $table->string('code');
        $table->unsignedInteger('enrollment_limit');
        $table->date('expires_at');
        $table->timestamps();
    });
    Schema::create('campaign_consultation_quotas', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('campaign_id');
        $table->unsignedBigInteger('workspace_id');
        $table->string('consultation_type');
        $table->unsignedInteger('quantity');
        $table->decimal('unit_fee', 16, 2);
        $table->decimal('total_cost', 16, 2);
        $table->string('reference');
        $table->timestamps();
    });
    Schema::create('campaign_recurring_costs', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('campaign_id');
        $table->unsignedBigInteger('workspace_id');
        $table->unsignedBigInteger('campaign_booth_id')->nullable();
        $table->string('name');
        $table->string('category');
        $table->decimal('monthly_amount', 16, 2);
        $table->char('currency', 3);
        $table->date('starts_on');
        $table->date('next_charge_on')->nullable();
        $table->date('ends_on')->nullable();
        $table->boolean('is_active');
        $table->timestamp('deactivated_at')->nullable();
        $table->timestamps();
    });
    Schema::create('campaign_booths', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('campaign_id');
        $table->timestamps();
    });
    Schema::create('transactions', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('recorded_by_user_id')->nullable();
        $table->string('owner_type');
        $table->unsignedBigInteger('owner_id');
        $table->string('transactable_type');
        $table->unsignedBigInteger('transactable_id');
        $table->decimal('amount', 16, 2);
        $table->char('currency', 3);
        $table->string('reference')->unique();
        $table->string('type');
        $table->string('status');
        $table->string('flow');
        $table->json('meta')->nullable();
        $table->timestamps();
    });
});

it('resolves the configured beneficiary boundary and calculates launch pricing', function (): void {
    CampaignPricingTier::factory()->create();
    CampaignPricingTier::factory()->openEnded()->create();

    $service = app(CampaignPricingService::class);
    $oneHundred = $service->quote(100, 'ngn');
    $oneHundredAndOne = $service->quote(101, 'NGN');

    expect($oneHundred->beneficiaryUnitFee)->toBe('1500.00')
        ->and($oneHundred->beneficiarySetupFee)->toBe('150000.00')
        ->and($oneHundred->monthlyServiceFee)->toBe('100000.00')
        ->and($oneHundredAndOne->beneficiaryUnitFee)->toBe('1000.00')
        ->and($oneHundredAndOne->beneficiarySetupFee)->toBe('101000.00');
});

it('rejects overlapping active pricing ranges', function (): void {
    CampaignPricingTier::factory()->create();
    $administrator = new User;
    $administrator->forceFill(['id' => 10, 'role' => Roles::ADMIN]);
    $administrator->exists = true;

    app(SaveCampaignPricingTierAction::class)->execute(
        new SaveCampaignPricingTierData(
            minimumBeneficiaries: 50,
            maximumBeneficiaries: 150,
            beneficiaryUnitFee: '1200.00',
            monthlyServiceFee: '100000.00',
            currency: 'NGN',
            isActive: true,
            sortOrder: 2,
        ),
        $administrator,
    );
})->throws(ValidationException::class);

it('allows inactive ranges to overlap active pricing', function (): void {
    CampaignPricingTier::factory()->create();
    $administrator = new User;
    $administrator->forceFill(['id' => 10, 'role' => Roles::ADMIN]);
    $administrator->exists = true;

    $tier = app(SaveCampaignPricingTierAction::class)->execute(
        new SaveCampaignPricingTierData(
            minimumBeneficiaries: 50,
            maximumBeneficiaries: 150,
            beneficiaryUnitFee: '1200.00',
            monthlyServiceFee: null,
            currency: 'NGN',
            isActive: false,
            sortOrder: 2,
        ),
        $administrator,
    );

    expect($tier->is_active)->toBeFalse()
        ->and($tier->monthly_service_fee)->toBeNull()
        ->and($tier->updated_by_user_id)->toBe(10);
});

it('preserves an omitted monthly campaign service fee', function (): void {
    $tier = CampaignPricingTier::factory()->withoutMonthlyServiceFee()->create();
    $quote = app(CampaignPricingService::class)->quote(100, 'NGN');

    expect($tier->monthly_service_fee)->toBeNull()
        ->and($quote->monthlyServiceFee)->toBeNull();
});

it('charges the beneficiary setup and schedules the monthly campaign service fee at launch', function (): void {
    CampaignPricingTier::factory()->create();
    $workspace = Workspace::query()->create([
        'name' => 'Hope Alive Foundation',
        'type' => AccountTypes::INSTITUTION,
    ]);
    $workspace->wallet()->update(['balance' => '1000000.00']);
    $administrator = new User;
    $administrator->forceFill(['id' => 10, 'role' => Roles::ADMIN]);
    $administrator->exists = true;

    $campaign = app(CreateCampaignAction::class)->execute(new CreateCampaignData(
        workspace: $workspace,
        user: $administrator,
        details: new CampaignDetailsData(
            name: 'Community Health 2026',
            description: null,
            locations: 'Sabon Gari',
            startDate: today()->toDateString(),
            endDate: today()->addMonth()->toDateString(),
        ),
        enrollment: new CampaignEnrollmentData(
            method: CampaignEnrollmentMethod::Manual,
            estimatedBeneficiaries: 100,
        ),
        healthcare: new CampaignHealthcareAllocationData(
            scheduledUnits: 1,
            instantUnits: 0,
            medicationBudget: '0.00',
            laboratoryBudget: '0.00',
        ),
        booth: new CampaignBoothData(
            required: false,
            count: null,
            preferredDeploymentDate: null,
            site: null,
            contactName: null,
            contactPhone: null,
        ),
    ));

    $serviceCost = CampaignRecurringCost::query()->firstOrFail();

    expect($campaign->beneficiary_unit_fee)->toBe('1500.00')
        ->and($campaign->beneficiary_setup_fee)->toBe('150000.00')
        ->and($campaign->monthly_service_fee)->toBe('100000.00')
        ->and($workspace->wallet()->value('balance'))->toBe('849000.00')
        ->and($serviceCost->category)->toBe(CampaignRecurringCostCategory::CampaignService)
        ->and($serviceCost->monthly_amount)->toBe('100000.00')
        ->and($serviceCost->next_charge_on?->toDateString())->toBe(today()->toDateString())
        ->and(Transaction::query()->where('type', TransactionTypes::CAMPAIGN_BENEFICIARY_SETUP)->value('amount'))->toBe('150000.00');
});

it('does not schedule a recurring campaign service cost when the fee is omitted', function (): void {
    CampaignPricingTier::factory()->withoutMonthlyServiceFee()->create();
    $workspace = Workspace::query()->create([
        'name' => 'Hope Alive Foundation',
        'type' => AccountTypes::INSTITUTION,
    ]);
    $workspace->wallet()->update(['balance' => '1000000.00']);
    $administrator = new User;
    $administrator->forceFill(['id' => 10, 'role' => Roles::ADMIN]);
    $administrator->exists = true;

    $campaign = app(CreateCampaignAction::class)->execute(new CreateCampaignData(
        workspace: $workspace,
        user: $administrator,
        details: new CampaignDetailsData(
            name: 'Community Health Without Service Fee',
            description: null,
            locations: 'Sabon Gari',
            startDate: today()->toDateString(),
            endDate: today()->addMonth()->toDateString(),
        ),
        enrollment: new CampaignEnrollmentData(
            method: CampaignEnrollmentMethod::Manual,
            estimatedBeneficiaries: 100,
        ),
        healthcare: new CampaignHealthcareAllocationData(
            scheduledUnits: 1,
            instantUnits: 0,
            medicationBudget: '0.00',
            laboratoryBudget: '0.00',
        ),
        booth: new CampaignBoothData(
            required: false,
            count: null,
            preferredDeploymentDate: null,
            site: null,
            contactName: null,
            contactPhone: null,
        ),
    ));

    expect($campaign->monthly_service_fee)->toBeNull()
        ->and(CampaignRecurringCost::query()->doesntExist())->toBeTrue()
        ->and($workspace->wallet()->value('balance'))->toBe('849000.00');
});
