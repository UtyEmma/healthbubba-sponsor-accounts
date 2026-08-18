<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workspace_beneficiaries', function (Blueprint $table) {
            $table->foreignId('campaign_id')
                ->nullable()
                ->after('workspace_id')
                ->constrained()
                ->nullOnDelete();

            $table->index(
                ['campaign_id', 'status'],
                'workspace_beneficiaries_campaign_status_index',
            );
        });
    }

    public function down(): void
    {
        Schema::table('workspace_beneficiaries', function (Blueprint $table) {
            $table->dropIndex('workspace_beneficiaries_campaign_status_index');
            $table->dropConstrainedForeignId('campaign_id');
        });
    }
};
