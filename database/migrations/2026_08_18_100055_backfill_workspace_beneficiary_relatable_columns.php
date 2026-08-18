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
        DB::table('workspace_beneficiaries')
            ->whereNotNull('campaign_id')
            ->update([
                'relatable_type' => 'App\\Models\\Campaign',
                'relatable_id' => DB::raw('campaign_id'),
            ]);

        DB::table('workspace_beneficiaries')
            ->whereNull('relatable_id')
            ->update([
                'relatable_type' => 'App\\Models\\Workspace',
                'relatable_id' => DB::raw('workspace_id'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('workspace_beneficiaries')->update([
            'relatable_type' => null,
            'relatable_id' => null,
        ]);
    }
};
