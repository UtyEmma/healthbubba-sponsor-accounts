<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workspace_beneficiaries', function (Blueprint $table): void {
            $table->index(
                ['workspace_id', 'created_at', 'id'],
                'workspace_beneficiaries_added_order_index',
            );
        });
    }

    public function down(): void
    {
        Schema::table('workspace_beneficiaries', function (Blueprint $table): void {
            $table->dropIndex('workspace_beneficiaries_added_order_index');
        });
    }
};
