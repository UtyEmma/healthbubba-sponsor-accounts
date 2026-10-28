<?php

use App\Enums\CampaignUsageBenefit;
use App\Enums\CampaignUsageSource;
use App\Enums\Consultations\ConsultationType;
use App\Models\Campaign;
use App\Models\CampaignConsultationQuota;
use App\Models\CampaignUsageEntry;
use App\Queries\InstitutionalCampaigns\CampaignMetricsQuery;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

    Schema::dropIfExists('consultations');
    Schema::dropIfExists('workspace_beneficiaries');
    Schema::dropIfExists('campaign_usage_entries');
    Schema::dropIfExists('campaign_consultation_quotas');

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
    Schema::create('campaign_usage_entries', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('campaign_id');
        $table->unsignedBigInteger('workspace_id');
        $table->unsignedBigInteger('workspace_beneficiary_id')->nullable();
        $table->unsignedBigInteger('recorded_by_user_id')->nullable();
        $table->string('benefit');
        $table->unsignedInteger('quantity')->nullable();
        $table->decimal('unit_amount', 16, 2)->nullable();
        $table->decimal('total_amount', 16, 2);
        $table->char('currency', 3);
        $table->string('source');
        $table->string('source_reference')->nullable();
        $table->string('reference');
        $table->timestamp('occurred_at');
        $table->json('meta')->nullable();
        $table->timestamps();
    });
    Schema::create('workspace_beneficiaries', function (Blueprint $table): void {
        $table->id();
        $table->string('relatable_type');
        $table->unsignedBigInteger('relatable_id');
    });
    Schema::create('consultations', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('workspace_beneficiary_id');
        $table->string('consultation_type');
        $table->string('status');
    });
});

it('uses purchased quota totals and recorded usage amounts for campaign financial metrics', function (): void {
    $campaign = new Campaign;
    $campaign->forceFill([
        'id' => 10,
        'workspace_id' => 20,
        'currency' => 'NGN',
        'medication_budget' => '500.00',
        'laboratory_budget' => '250.00',
        'returned_amount' => '0.00',
    ]);
    $campaign->exists = true;

    CampaignConsultationQuota::query()->create([
        'campaign_id' => $campaign->getKey(),
        'workspace_id' => 20,
        'consultation_type' => ConsultationType::Scheduled,
        'quantity' => 2,
        'unit_fee' => '1000.00',
        'total_cost' => '2000.00',
        'reference' => 'CAMPAIGN-QUOTA-ONE',
    ]);
    CampaignConsultationQuota::query()->create([
        'campaign_id' => $campaign->getKey(),
        'workspace_id' => 20,
        'consultation_type' => ConsultationType::Scheduled,
        'quantity' => 1,
        'unit_fee' => '1500.00',
        'total_cost' => '1500.00',
        'reference' => 'CAMPAIGN-QUOTA-TWO',
    ]);
    CampaignUsageEntry::query()->create([
        'campaign_id' => $campaign->getKey(),
        'workspace_id' => 20,
        'benefit' => CampaignUsageBenefit::ScheduledConsultation,
        'quantity' => 2,
        'unit_amount' => '1100.00',
        'total_amount' => '2200.00',
        'currency' => 'NGN',
        'source' => CampaignUsageSource::Provider,
        'reference' => 'CAMPAIGN-USAGE-CONSULTATION',
        'occurred_at' => now(),
    ]);
    CampaignUsageEntry::query()->create([
        'campaign_id' => $campaign->getKey(),
        'workspace_id' => 20,
        'benefit' => CampaignUsageBenefit::Medication,
        'total_amount' => '100.00',
        'currency' => 'NGN',
        'source' => CampaignUsageSource::Manual,
        'reference' => 'CAMPAIGN-USAGE-MEDICATION',
        'occurred_at' => now(),
    ]);

    app(CampaignMetricsQuery::class)->hydrate(new Collection([$campaign]));

    $metrics = $campaign->getAttribute('financial_metrics');

    expect($metrics)->toMatchArray([
        'allocated' => '4250.00',
        'utilized' => '2300.00',
        'consultationUtilized' => '2200.00',
        'reserved' => '1950.00',
        'utilizationPercentage' => 54,
    ])->and($metrics['consultations']['scheduled'])->toMatchArray([
        'units' => 3,
        'confirmed' => 2,
        'allocated' => '3500.00',
    ]);
});
