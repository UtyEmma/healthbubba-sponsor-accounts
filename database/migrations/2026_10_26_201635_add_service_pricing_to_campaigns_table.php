<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table): void {
            $table->foreignId('campaign_pricing_tier_id')
                ->nullable()
                ->after('estimated_beneficiaries')
                ->constrained()
                ->nullOnDelete();
            $table->decimal('beneficiary_unit_fee', 16, 2)
                ->nullable()
                ->after('campaign_pricing_tier_id');
            $table->decimal('beneficiary_setup_fee', 16, 2)
                ->nullable()
                ->after('beneficiary_unit_fee');
            $table->decimal('monthly_service_fee', 16, 2)
                ->nullable()
                ->after('beneficiary_setup_fee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('campaign_pricing_tier_id');
            $table->dropColumn([
                'beneficiary_unit_fee',
                'beneficiary_setup_fee',
                'monthly_service_fee',
            ]);
        });
    }
};
