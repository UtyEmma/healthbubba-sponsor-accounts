<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workspaces', function (Blueprint $table): void {
            $table->uuid('referrer_id')->nullable()->index();
            $table->timestamp('referrer_linked_at')->nullable();
        });
        Schema::table('payments', function (Blueprint $table): void {
            $table->timestamp('refunded_at')->nullable();
            $table->timestamp('charged_back_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropColumn(['refunded_at', 'charged_back_at']);
        });
        Schema::table('workspaces', function (Blueprint $table): void {
            $table->dropColumn(['referrer_id', 'referrer_linked_at']);
        });
    }
};
