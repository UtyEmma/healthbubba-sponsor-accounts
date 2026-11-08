<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('subscriptions')
            ->whereNotNull('scheduled_billing_cycle')
            ->update(['scheduled_billing_cycle' => null]);
    }

    public function down(): void
    {
        // Cleared scheduling decisions cannot be reconstructed safely.
    }
};
