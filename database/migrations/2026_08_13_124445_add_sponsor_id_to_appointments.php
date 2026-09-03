<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('main_sql')->table('appointments', function (Blueprint $table) {
            if(!Schema::connection('main_sql')->hasColumn('appointments', 'sponsor_id')) {
                $table->string('sponsor_id')->after('patient_id')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('main_sql')->table('appointments', function (Blueprint $table) {
            $table->dropColumn('sponsor_id');
        });
    }
};
