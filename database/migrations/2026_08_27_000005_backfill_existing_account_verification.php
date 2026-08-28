<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->whereNull('account_verified_at')
            ->update([
                'account_verified_at' => DB::raw('COALESCE(email_verified_at, created_at)'),
            ]);
    }

    public function down(): void
    {
        // Existing accounts remain verified if this data migration is rolled back.
    }
};
