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
        if (DB::table('campaign_pricing_tiers')->exists()) {
            return;
        }

        $now = now();

        DB::table('campaign_pricing_tiers')->insert([
            [
                'minimum_beneficiaries' => 1,
                'maximum_beneficiaries' => 100,
                'beneficiary_unit_fee' => '1500.00',
                'monthly_service_fee' => '',
                'currency' => 'NGN',
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'minimum_beneficiaries' => 101,
                'maximum_beneficiaries' => null,
                'beneficiary_unit_fee' => '1000.00',
                'monthly_service_fee' => '100000.00',
                'currency' => 'NGN',
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('campaign_pricing_tiers')
            ->where('currency', 'NGN')
            ->where('monthly_service_fee', '100000.00')
            ->where(function ($query): void {
                $query->where(function ($query): void {
                    $query->where('minimum_beneficiaries', 1)
                        ->where('maximum_beneficiaries', 100)
                        ->where('beneficiary_unit_fee', '1500.00');
                })->orWhere(function ($query): void {
                    $query->where('minimum_beneficiaries', 101)
                        ->whereNull('maximum_beneficiaries')
                        ->where('beneficiary_unit_fee', '1000.00');
                });
            })
            ->delete();
    }
};
