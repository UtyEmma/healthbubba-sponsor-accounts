<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('workspaces')
            ->where('type', 'institution')
            ->orderBy('id')
            ->each(function (object $workspace): void {
                $startsOn = Carbon::parse($workspace->created_at)->startOfDay();

                DB::table('institutional_funding_programs')->insertOrIgnore([
                    'workspace_id' => $workspace->id,
                    'name' => 'Community Health Program '.$startsOn->year,
                    'starts_on' => $startsOn->toDateString(),
                    'ends_on' => $startsOn->copy()->addYearNoOverflow()->toDateString(),
                    'coverage_type' => 'shared_pool',
                    'gp_limit_per_beneficiary' => 4,
                    'specialist_limit_per_beneficiary' => 2,
                    'daily_consultation_limit' => 1,
                    'expiry_cadence' => 'annual',
                    'payment_preference' => 'user_choice',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Programs may have been edited after backfill; a forward migration is safer than deleting them.
    }
};
