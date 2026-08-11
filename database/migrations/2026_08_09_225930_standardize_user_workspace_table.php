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
        if (Schema::hasTable('workspace_user') && ! Schema::hasTable('user_workspace')) {
            Schema::rename('workspace_user', 'user_workspace');
        }

        if (! Schema::hasTable('user_workspace')) {
            return;
        }

        Schema::table('user_workspace', function (Blueprint $table): void {
            $table->unsignedBigInteger('user_id')->change();
            $table->unsignedBigInteger('workspace_id')->change();
        });

        if (! Schema::hasForeignKey('user_workspace', ['user_id'])) {
            Schema::table('user_workspace', function (Blueprint $table): void {
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        if (! Schema::hasForeignKey('user_workspace', ['workspace_id'])) {
            Schema::table('user_workspace', function (Blueprint $table): void {
                $table->foreign('workspace_id')->references('id')->on('workspaces')->cascadeOnDelete();
            });
        }

        if (! Schema::hasIndex('user_workspace', ['user_id', 'workspace_id'], 'unique')) {
            Schema::table('user_workspace', function (Blueprint $table): void {
                $table->unique(['user_id', 'workspace_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('user_workspace')) {
            return;
        }

        $hasUniqueMemberships = Schema::hasIndex(
            'user_workspace',
            ['user_id', 'workspace_id'],
            'unique',
        );
        $hasUserForeignKey = Schema::hasForeignKey('user_workspace', ['user_id']);
        $hasWorkspaceForeignKey = Schema::hasForeignKey('user_workspace', ['workspace_id']);

        Schema::table('user_workspace', function (Blueprint $table) use (
            $hasUniqueMemberships,
            $hasUserForeignKey,
            $hasWorkspaceForeignKey,
        ): void {
            if ($hasUniqueMemberships) {
                $table->dropUnique(['user_id', 'workspace_id']);
            }

            if ($hasUserForeignKey) {
                $table->dropForeign(['user_id']);
            }

            if ($hasWorkspaceForeignKey) {
                $table->dropForeign(['workspace_id']);
            }

            $table->string('user_id')->change();
            $table->string('workspace_id')->change();
        });
    }
};
