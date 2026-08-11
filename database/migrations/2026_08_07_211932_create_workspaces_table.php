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
        if (! Schema::hasTable('workspaces')) {
            Schema::create('workspaces', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('logo')->nullable();
                $table->string('type');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('user_workspace')) {
            Schema::create('user_workspace', function (Blueprint $table): void {
                $table->foreignId('user_id');
                $table->foreignId('workspace_id');
                $table->string('role');
                $table->string('status');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // These tables may have predated this repaired migration, so rollback
        // intentionally leaves them for a forward migration or `migrate:fresh`.
    }
};
