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
        Schema::table('workspace_beneficiaries', function (Blueprint $table) {
            $table->foreignId('workspace_id')->after('id')->constrained()->cascadeOnDelete();
            $table->foreignId('invited_by_user_id')->nullable()->after('workspace_id')->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('beneficiary_id')->nullable()->after('invited_by_user_id')->index();
            $table->char('public_id', 26)->after('beneficiary_id')->unique();
            $table->string('first_name', 100)->after('public_id');
            $table->string('last_name', 100)->after('first_name');
            $table->string('email')->after('last_name');
            $table->string('phone', 32)->after('email');
            $table->string('department', 120)->nullable()->after('phone');
            $table->string('employee_id', 32)->nullable()->after('department');
            $table->enum('status', ['pending', 'active', 'declined', 'cancelled', 'expired'])
                ->default('pending')
                ->after('employee_id');
            $table->enum('source', ['manual', 'import'])
                ->default('manual')
                ->after('status');
            $table->unsignedInteger('invitation_version')->default(1)->after('source');
            $table->timestamp('invited_at')->after('invitation_version');
            $table->timestamp('expires_at')->after('invited_at');
            $table->timestamp('accepted_at')->nullable()->after('expires_at');
            $table->timestamp('declined_at')->nullable()->after('accepted_at');
            $table->timestamp('cancelled_at')->nullable()->after('declined_at');

            $table->unique(['workspace_id', 'email']);
            $table->unique(['workspace_id', 'employee_id']);
            $table->index(['workspace_id', 'status', 'expires_at'], 'workspace_beneficiaries_capacity_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workspace_beneficiaries', function (Blueprint $table) {
            $table->dropForeign(['workspace_id']);
            $table->dropForeign(['invited_by_user_id']);
            $table->dropUnique(['workspace_id', 'email']);
            $table->dropUnique(['workspace_id', 'employee_id']);
            $table->dropIndex('workspace_beneficiaries_capacity_index');
            $table->dropUnique(['public_id']);
            $table->dropIndex(['beneficiary_id']);
            $table->dropColumn([
                'workspace_id',
                'invited_by_user_id',
                'beneficiary_id',
                'public_id',
                'first_name',
                'last_name',
                'email',
                'phone',
                'department',
                'employee_id',
                'status',
                'source',
                'invitation_version',
                'invited_at',
                'expires_at',
                'accepted_at',
                'declined_at',
                'cancelled_at',
            ]);
        });
    }
};
