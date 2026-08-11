<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('subscriptions')
            ->join('workspaces', function ($join): void {
                $join->on('workspaces.id', '=', 'subscriptions.subscribable_id')
                    ->where('subscriptions.subscribable_type', '=', 'App\\Models\\Workspace');
            })
            ->where('workspaces.type', 'individual')
            ->select(['subscriptions.id', 'subscriptions.plan_id', 'subscriptions.capacity_count'])
            ->orderBy('subscriptions.id')
            ->each(function (object $subscription): void {
                $includedCapacity = DB::table('feature_plan')
                    ->join('features', 'features.id', '=', 'feature_plan.feature_id')
                    ->where('feature_plan.plan_id', $subscription->plan_id)
                    ->where('features.slug', 'beneficiaries-included')
                    ->value('feature_plan.value');

                if (! is_numeric($includedCapacity)) {
                    return;
                }

                DB::table('subscriptions')
                    ->where('id', $subscription->id)
                    ->update([
                        'capacity_count' => max(
                            (int) $subscription->capacity_count,
                            (int) $includedCapacity,
                        ),
                    ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Existing capacity purchases make this backfill intentionally irreversible.
    }
};
