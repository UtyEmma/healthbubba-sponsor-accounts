<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::connection('main_sql')->hasIndex('appointments', 'appointments_sponsor_id_index')) {
            return;
        }

        Schema::connection('main_sql')->table('appointments', function (Blueprint $table): void {
            $table->index('sponsor_id', 'appointments_sponsor_id_index');
        });
    }

    public function down(): void
    {
        if (! Schema::connection('main_sql')->hasIndex('appointments', 'appointments_sponsor_id_index')) {
            return;
        }

        Schema::connection('main_sql')->table('appointments', function (Blueprint $table): void {
            $table->dropIndex('appointments_sponsor_id_index');
        });
    }
};
