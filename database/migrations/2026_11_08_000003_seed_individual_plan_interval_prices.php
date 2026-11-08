<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $prices = [
            'individual-basic' => [
                'monthly' => '20000.00',
                'quarterly' => '57000.00',
                'biannual' => '108000.00',
                'annual' => '204000.00',
            ],
            'individual-premium' => [
                'monthly' => '39000.00',
                'quarterly' => '111150.00',
                'biannual' => '210600.00',
                'annual' => '399000.00',
            ],
            'individual-coordinated-care' => [
                'monthly' => '59000.00',
                'quarterly' => '168150.00',
                'biannual' => '318600.00',
                'annual' => '600000.00',
            ],
        ];

        foreach ($prices as $slug => $cycles) {
            $planId = DB::table('plans')->where('slug', $slug)->value('id');

            if (! is_numeric($planId)) {
                continue;
            }

            $planId = (int) $planId;

            DB::table('plans')->where('id', $planId)->update([
                'price' => $cycles['monthly'],
                'billing_period' => 1,
                'billing_interval' => 'month',
                'updated_at' => now(),
            ]);

            foreach (['quarterly', 'biannual', 'annual'] as $sort => $cycle) {
                DB::table('plan_interval_prices')->updateOrInsert(
                    ['plan_id' => $planId, 'billing_cycle' => $cycle],
                    [
                        'price' => $cycles[$cycle],
                        'is_active' => true,
                        'sort_order' => $sort + 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );
            }
        }

        DB::table('subscriptions')
            ->whereIn('plan_id', DB::table('plans')->select('id')->where('account_type', 'individual'))
            ->whereNull('billing_cycle')
            ->update(['billing_cycle' => 'monthly']);
    }

    public function down(): void
    {
        $planIds = DB::table('plans')
            ->whereIn('slug', [
                'individual-basic',
                'individual-premium',
                'individual-coordinated-care',
            ])
            ->pluck('id');

        DB::table('plan_interval_prices')->whereIn('plan_id', $planIds)->delete();
        DB::table('subscriptions')->whereIn('plan_id', $planIds)->update([
            'billing_cycle' => null,
            'scheduled_billing_cycle' => null,
        ]);
    }
};
