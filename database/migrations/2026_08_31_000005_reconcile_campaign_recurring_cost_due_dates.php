<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('campaign_recurring_costs')
            ->orderBy('id')
            ->each(function (object $cost): void {
                $pendingPeriod = DB::table('campaign_recurring_cost_charges')
                    ->where('campaign_recurring_cost_id', $cost->id)
                    ->where('status', 'pending')
                    ->oldest('service_period')
                    ->value('service_period');

                if ($pendingPeriod !== null) {
                    $nextChargeOn = CarbonImmutable::parse((string) $pendingPeriod)->toDateString();
                } else {
                    $paidThrough = $cost->campaign_booth_id === null
                        ? null
                        : DB::table('campaign_booths')
                            ->where('id', $cost->campaign_booth_id)
                            ->value('paid_through');
                    $latestPaidPeriod = DB::table('campaign_recurring_cost_charges')
                        ->where('campaign_recurring_cost_id', $cost->id)
                        ->where('status', 'paid')
                        ->latest('service_period')
                        ->value('service_period');
                    $nextChargeOn = match (true) {
                        $paidThrough !== null => CarbonImmutable::parse((string) $paidThrough)->addDay()->toDateString(),
                        $latestPaidPeriod !== null => CarbonImmutable::parse((string) $latestPaidPeriod)->addMonthNoOverflow()->toDateString(),
                        default => CarbonImmutable::parse($cost->starts_on)->toDateString(),
                    };
                }

                DB::table('campaign_recurring_costs')
                    ->where('id', $cost->id)
                    ->update(['next_charge_on' => $nextChargeOn]);
            });
    }

    public function down(): void
    {
        // The previous due date cannot be reconstructed safely.
    }
};
