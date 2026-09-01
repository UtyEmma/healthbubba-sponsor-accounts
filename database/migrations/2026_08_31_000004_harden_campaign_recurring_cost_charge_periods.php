<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaign_recurring_cost_charges', function (Blueprint $table): void {
            $table->unique(
                ['campaign_recurring_cost_id', 'service_period'],
                'campaign_cost_period_unique',
            );
            $table->index(['status', 'service_period'], 'campaign_cost_charge_status_period_index');
        });
    }

    public function down(): void
    {
        Schema::table('campaign_recurring_cost_charges', function (Blueprint $table): void {
            $table->dropUnique('campaign_cost_period_unique');
            $table->dropIndex('campaign_cost_charge_status_period_index');
        });
    }
};
