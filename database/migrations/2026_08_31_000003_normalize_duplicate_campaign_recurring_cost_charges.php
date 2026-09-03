<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('campaign_recurring_cost_charges')
            ->select('campaign_recurring_cost_id', 'service_period')
            ->selectRaw('COUNT(*) AS aggregate')
            ->groupBy('campaign_recurring_cost_id', 'service_period')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $charges = DB::table('campaign_recurring_cost_charges')
                ->where('campaign_recurring_cost_id', $duplicate->campaign_recurring_cost_id)
                ->where('service_period', $duplicate->service_period)
                ->orderByRaw("CASE WHEN status = 'paid' THEN 0 ELSE 1 END")
                ->oldest('id')
                ->get();
            $canonical = $charges->first();

            if ($canonical === null) {
                continue;
            }

            foreach ($charges->skip(1) as $charge) {
                DB::table('transactions')
                    ->where('transactable_type', 'App\\Models\\CampaignRecurringCostCharge')
                    ->where('transactable_id', $charge->id)
                    ->update(['transactable_id' => $canonical->id]);

                DB::table('campaign_recurring_cost_charges')->where('id', $charge->id)->delete();
            }
        }
    }

    public function down(): void
    {
        // Duplicate charge rows cannot be reconstructed safely.
    }
};
