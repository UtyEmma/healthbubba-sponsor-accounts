<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_workspace', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
        });

        Schema::table('user_workspace', function (Blueprint $table): void {
            $table->id()->first();
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->ulid('public_id')->nullable()->unique()->after('id');
            $table->string('name')->nullable()->after('workspace_id');
            $table->string('email')->nullable()->after('name');
            $table->foreignId('invited_by_user_id')->nullable()->after('email')
                ->constrained('users')->nullOnDelete();
            $table->unsignedInteger('invitation_version')->default(1)->after('status');
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('disabled_at')->nullable();
            $table->timestamp('last_selected_at')->nullable();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['workspace_id', 'email'], 'user_workspace_workspace_email_unique');
            $table->index(['workspace_id', 'status', 'created_at'], 'user_workspace_status_created_index');
            $table->index(['user_id', 'status', 'last_selected_at'], 'user_workspace_user_status_selected_index');
        });
    }

    public function down(): void
    {
        // This forward-only expansion may contain invitations without users.
        // Reversing it would discard team history and is intentionally unsupported.
    }
};
