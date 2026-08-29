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
            $table->string('coverage_type_override', 32)->nullable()->after('status');
            $table->unsignedSmallInteger('gp_limit_per_beneficiary_override')->nullable()->after('coverage_type_override');
            $table->unsignedSmallInteger('specialist_limit_per_beneficiary_override')->nullable()->after('gp_limit_per_beneficiary_override');
            $table->unsignedSmallInteger('daily_consultation_limit_override')->nullable()->after('specialist_limit_per_beneficiary_override');
            $table->string('coverage_expiry_override', 24)->nullable()->after('daily_consultation_limit_override');
            $table->string('payment_preference_override', 32)->nullable()->after('coverage_expiry_override');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table): void {
            $table->dropColumn([
                'coverage_type_override',
                'gp_limit_per_beneficiary_override',
                'specialist_limit_per_beneficiary_override',
                'daily_consultation_limit_override',
                'coverage_expiry_override',
                'payment_preference_override',
            ]);
        });
    }
};
