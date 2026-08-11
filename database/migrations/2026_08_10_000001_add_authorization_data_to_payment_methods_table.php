<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_methods', function (Blueprint $table): void {
            $table->text('authorization_data')->nullable()->after('authorization_code');
        });
    }

    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table): void {
            $table->dropColumn('authorization_data');
        });
    }
};
