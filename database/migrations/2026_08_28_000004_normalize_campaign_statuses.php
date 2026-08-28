<?php

use App\Enums\CampaignStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('campaigns')->update([
            'status' => DB::raw("CASE WHEN end_date < CURRENT_DATE THEN '".CampaignStatus::COMPLETED->value."' WHEN start_date > CURRENT_DATE THEN '".CampaignStatus::PENDING->value."' ELSE '".CampaignStatus::IN_PROGRESS->value."' END"),
        ]);
    }

    public function down(): void
    {
        // Normalized lifecycle values intentionally remain valid on rollback.
    }
};
