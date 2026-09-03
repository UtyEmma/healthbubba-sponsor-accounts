<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('campaigns')
            ->whereNotNull('display_enrollment_code')
            ->orderBy('id')
            ->each(function (object $campaign): void {
                DB::table('campaign_enrollment_codes')->insertOrIgnore([
                    'public_id' => (string) Str::ulid(),
                    'campaign_id' => $campaign->id,
                    'created_by_user_id' => null,
                    'code' => $campaign->display_enrollment_code,
                    'enrollment_limit' => max(1, (int) ($campaign->beneficiary_limit ?? $campaign->estimated_beneficiaries ?? 1)),
                    'expires_at' => $campaign->end_date ?? now()->addYear()->toDateString(),
                    'created_at' => $campaign->created_at ?? now(),
                    'updated_at' => $campaign->updated_at ?? now(),
                ]);
            });
    }

    public function down(): void
    {
        // The schema migration owns table rollback.
    }
};
