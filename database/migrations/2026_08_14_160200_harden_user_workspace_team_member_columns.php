<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_workspace', function (Blueprint $table): void {
            $table->ulid('public_id')->nullable(false)->change();
            $table->string('name')->nullable(false)->change();
            $table->string('email')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('user_workspace', function (Blueprint $table): void {
            $table->ulid('public_id')->nullable()->change();
            $table->string('name')->nullable()->change();
            $table->string('email')->nullable()->change();
        });
    }
};
