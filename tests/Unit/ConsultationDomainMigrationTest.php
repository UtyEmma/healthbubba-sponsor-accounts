<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $sqlite = config('database.connections.sqlite');
    $sqlite['database'] = ':memory:';

    config([
        'database.default' => 'mysql',
        'database.connections.mysql' => $sqlite,
        'database.connections.main_sql' => $sqlite,
        'subscriptionify.tables.features' => 'features',
    ]);

    DB::purge('mysql');
    DB::purge('main_sql');

    Schema::create('consultations', function (Blueprint $table): void {
        $table->id();
        $table->unsignedInteger('appointment_id')->nullable();
        $table->string('consultation_type');
        $table->string('feature_slug');
    });
    Schema::create('campaign_consultation_quotas', function (Blueprint $table): void {
        $table->id();
        $table->string('consultation_type');
    });
    Schema::create('campaigns', function (Blueprint $table): void {
        $table->id();
        $table->decimal('scheduled_consultation_fee', 16, 2)->nullable();
        $table->decimal('instant_consultation_fee', 16, 2)->nullable();
    });
    Schema::create('campaign_usage_entries', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('campaign_id');
        $table->string('benefit');
        $table->unsignedInteger('quantity')->nullable();
        $table->decimal('unit_amount', 16, 2)->nullable();
        $table->decimal('total_amount', 16, 2);
        $table->string('source_reference')->nullable();
    });
    Schema::create('features', function (Blueprint $table): void {
        $table->id();
        $table->string('slug')->unique();
        $table->string('name');
        $table->text('description')->nullable();
        $table->timestamps();
    });
    Schema::connection('main_sql')->create('appointments', function (Blueprint $table): void {
        $table->unsignedInteger('appointment_id')->primary();
        $table->boolean('is_urgent_consultation')->nullable();
    });
});

it('translates legacy values and reclassifies linked history from appointment urgency', function (): void {
    DB::table('campaigns')->insert([
        'id' => 1,
        'scheduled_consultation_fee' => '1000.00',
        'instant_consultation_fee' => '5000.00',
    ]);
    DB::table('consultations')->insert([
        [
            'id' => 1,
            'appointment_id' => 101,
            'consultation_type' => 'specialist',
            'feature_slug' => 'campaign-specialist-consultations',
        ],
        [
            'id' => 2,
            'appointment_id' => 102,
            'consultation_type' => 'gp',
            'feature_slug' => 'gp-consultations-per-seat',
        ],
        [
            'id' => 3,
            'appointment_id' => null,
            'consultation_type' => 'specialist',
            'feature_slug' => 'specialist-consultations',
        ],
    ]);
    DB::table('campaign_consultation_quotas')->insert([
        ['consultation_type' => 'gp'],
        ['consultation_type' => 'specialist'],
    ]);
    DB::table('campaign_usage_entries')->insert([
        [
            'id' => 1,
            'campaign_id' => 1,
            'benefit' => 'specialist',
            'quantity' => 1,
            'unit_amount' => '5000.00',
            'total_amount' => '5000.00',
            'source_reference' => 'consultation:1',
        ],
        [
            'id' => 2,
            'campaign_id' => 1,
            'benefit' => 'gp',
            'quantity' => 2,
            'unit_amount' => '1000.00',
            'total_amount' => '2000.00',
            'source_reference' => 'consultation:2',
        ],
        [
            'id' => 3,
            'campaign_id' => 1,
            'benefit' => 'specialist',
            'quantity' => 1,
            'unit_amount' => '777.00',
            'total_amount' => '777.00',
            'source_reference' => 'manual:3',
        ],
    ]);
    DB::table('features')->insert([
        [
            'id' => 11,
            'slug' => 'gp-consultations',
            'name' => 'Scheduled Consultations',
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'id' => 12,
            'slug' => 'specialist-consultations',
            'name' => 'Instant Consultations',
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);
    DB::connection('main_sql')->table('appointments')->insert([
        ['appointment_id' => 101, 'is_urgent_consultation' => false],
        ['appointment_id' => 102, 'is_urgent_consultation' => true],
    ]);

    $migration = require database_path('migrations/2026_10_12_213544_migrate_consultation_domain_values.php');

    if (! $migration instanceof Migration) {
        throw new RuntimeException('The consultation-domain data migration could not be loaded.');
    }

    $migration->up();

    expect(DB::table('consultations')->where('id', 1)->first())->toMatchObject([
        'consultation_type' => 'scheduled',
        'feature_slug' => 'campaign-scheduled-consultations',
    ])->and(DB::table('consultations')->where('id', 2)->first())->toMatchObject([
        'consultation_type' => 'instant',
        'feature_slug' => 'instant-consultations-per-seat',
    ])->and(DB::table('consultations')->where('id', 3)->first())->toMatchObject([
        'consultation_type' => 'instant',
        'feature_slug' => 'instant-consultations',
    ])->and(DB::table('campaign_usage_entries')->where('id', 1)->first())->toMatchObject([
        'benefit' => 'scheduled',
        'unit_amount' => 1000,
        'total_amount' => 1000,
    ])->and(DB::table('campaign_usage_entries')->where('id', 2)->first())->toMatchObject([
        'benefit' => 'instant',
        'unit_amount' => 5000,
        'total_amount' => 10000,
    ])->and(DB::table('campaign_usage_entries')->where('id', 3)->first())->toMatchObject([
        'benefit' => 'instant',
        'unit_amount' => 777,
        'total_amount' => 777,
    ])->and(DB::table('features')->where('id', 11)->value('slug'))
        ->toBe('scheduled-consultations')
        ->and(DB::table('features')->where('id', 12)->value('slug'))
        ->toBe('instant-consultations')
        ->and(DB::table('campaign_consultation_quotas')->pluck('consultation_type')->sort()->values()->all())
        ->toBe(['instant', 'scheduled']);
});
