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
        Schema::table('payment_gateway_settings', function (Blueprint $table): void {
            $table->string('individual_gateway')->nullable()->after('active_gateway');
            $table->string('business_gateway')->nullable()->after('individual_gateway');
            $table->string('institution_gateway')->nullable()->after('business_gateway');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_gateway_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'individual_gateway',
                'business_gateway',
                'institution_gateway',
            ]);
        });
    }
};
