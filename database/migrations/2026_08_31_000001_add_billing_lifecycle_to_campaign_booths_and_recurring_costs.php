<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaign_booths', function (Blueprint $table): void {
            $table->date('billing_grace_ends_on')->nullable()->after('paid_through');
            $table->timestamp('billing_suspended_at')->nullable()->after('billing_grace_ends_on');
        });

        Schema::table('campaign_recurring_costs', function (Blueprint $table): void {
            $table->date('next_charge_on')->nullable()->after('starts_on');
            $table->index(['is_active', 'next_charge_on'], 'campaign_costs_due_index');
        });
    }

    public function down(): void
    {
        Schema::table('campaign_recurring_costs', function (Blueprint $table): void {
            $table->dropIndex('campaign_costs_due_index');
            $table->dropColumn('next_charge_on');
        });

        Schema::table('campaign_booths', function (Blueprint $table): void {
            $table->dropColumn(['billing_grace_ends_on', 'billing_suspended_at']);
        });
    }
};
