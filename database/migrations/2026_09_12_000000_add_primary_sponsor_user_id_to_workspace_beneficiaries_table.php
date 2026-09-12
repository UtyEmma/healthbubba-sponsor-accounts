<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workspace_beneficiaries', function (Blueprint $table): void {
            $table->foreignId('primary_sponsor_user_id')
                ->nullable()
                ->after('invited_by_user_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->unique(
                ['workspace_id', 'primary_sponsor_user_id'],
                'workspace_beneficiaries_primary_sponsor_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::table('workspace_beneficiaries', function (Blueprint $table): void {
            $table->dropUnique('workspace_beneficiaries_primary_sponsor_unique');
            $table->dropConstrainedForeignId('primary_sponsor_user_id');
        });
    }
};
