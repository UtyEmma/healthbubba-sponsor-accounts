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
                $pending = DB::table('campaign_recurring_cost_charges')
                    ->where('campaign_recurring_cost_id', $cost->id)
                    ->where('status', 'pending')
                    ->oldest('service_period')
                    ->oldest('id')
                    ->first();
                $booth = $cost->campaign_booth_id === null
                    ? null
                    : DB::table('campaign_booths')->where('id', $cost->campaign_booth_id)->first();
                $paidCount = DB::table('campaign_recurring_cost_charges')
                    ->where('campaign_recurring_cost_id', $cost->id)
                    ->where('status', 'paid')
                    ->count();
                $nextChargeOn = $pending?->service_period
                    ?? ($booth?->paid_through === null
                        ? CarbonImmutable::parse($cost->starts_on)->addMonthsNoOverflow($paidCount)->toDateString()
                        : CarbonImmutable::parse($booth->paid_through)->addDay()->toDateString());

                DB::table('campaign_recurring_costs')
                    ->where('id', $cost->id)
                    ->update(['next_charge_on' => $nextChargeOn]);

                if ($booth === null || $booth->status !== 'active' || $pending?->attempted_at === null) {
                    return;
                }

                $graceEndsOn = CarbonImmutable::parse($pending->service_period)->addDays(7);
                $suspended = CarbonImmutable::today()->greaterThanOrEqualTo($graceEndsOn);

                DB::table('campaign_booths')
                    ->where('id', $booth->id)
                    ->update([
                        'status' => $suspended ? 'suspended' : 'grace_period',
                        'billing_grace_ends_on' => $graceEndsOn->toDateString(),
                        'billing_suspended_at' => $suspended ? now() : null,
                    ]);
            });
    }

    public function down(): void
    {
        DB::table('campaign_booths')
            ->whereIn('status', ['grace_period', 'suspended'])
            ->update([
                'status' => 'active',
                'billing_grace_ends_on' => null,
                'billing_suspended_at' => null,
            ]);

        DB::table('campaign_recurring_costs')->update(['next_charge_on' => null]);
    }
};
