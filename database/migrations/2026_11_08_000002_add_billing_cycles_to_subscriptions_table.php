<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->string('billing_cycle')->nullable()->after('plan_id')->index();
            $table->string('scheduled_billing_cycle')->nullable()->after('billing_cycle')->index();
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropColumn(['billing_cycle', 'scheduled_billing_cycle']);
        });
    }
};
