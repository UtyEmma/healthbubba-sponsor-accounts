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
        Schema::create('campaign_pricing_tiers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('minimum_beneficiaries');
            $table->unsignedInteger('maximum_beneficiaries')->nullable();
            $table->decimal('beneficiary_unit_fee', 16, 2);
            $table->decimal('monthly_service_fee', 16, 2)->nullable();
            $table->char('currency', 3)->default('NGN');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(
                ['currency', 'is_active', 'minimum_beneficiaries', 'maximum_beneficiaries'],
                'campaign_pricing_tiers_lookup_index',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_pricing_tiers');
    }
};
