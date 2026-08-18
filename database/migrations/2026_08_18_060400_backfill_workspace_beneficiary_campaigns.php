<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('workspaces')
            ->where('type', 'institution')
            ->orderBy('id')
            ->chunkById(100, function ($workspaces): void {
                foreach ($workspaces as $workspace) {
                    $campaignId = DB::table('campaigns')
                        ->where('workspace_id', $workspace->id)
                        ->latest('id')
                        ->value('id');

                    if ($campaignId === null) {
                        continue;
                    }

                    DB::table('workspace_beneficiaries')
                        ->where('workspace_id', $workspace->id)
                        ->whereNull('campaign_id')
                        ->update(['campaign_id' => $campaignId]);
                }
            });
    }

    public function down(): void
    {
        DB::table('workspace_beneficiaries')
            ->whereIn('workspace_id', DB::table('workspaces')->where('type', 'institution')->select('id'))
            ->update(['campaign_id' => null]);
    }
};
