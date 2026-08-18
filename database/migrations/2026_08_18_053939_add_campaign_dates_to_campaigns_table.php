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
        Schema::table('campaigns', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('target_audience');
            $table->date('end_date')->nullable()->after('start_date');

            $table->index(['workspace_id', 'start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropIndex(['workspace_id', 'start_date', 'end_date']);
            $table->dropColumn(['start_date', 'end_date']);
        });
    }
};
