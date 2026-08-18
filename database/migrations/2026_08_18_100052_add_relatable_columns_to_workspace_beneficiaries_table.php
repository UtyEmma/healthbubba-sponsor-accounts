<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('workspace_beneficiaries', function (Blueprint $table): void {
            $table->string('relatable_type')
                ->nullable()
                ->after('campaign_id');
            $table->unsignedBigInteger('relatable_id')
                ->nullable()
                ->after('relatable_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workspace_beneficiaries', function (Blueprint $table): void {
            $table->dropColumn(['relatable_type', 'relatable_id']);
        });
    }
};
