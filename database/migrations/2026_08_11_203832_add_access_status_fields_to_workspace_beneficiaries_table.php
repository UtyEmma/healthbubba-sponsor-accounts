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
        Schema::table('workspace_beneficiaries', function (Blueprint $table) {
            $table->enum('status', [
                'pending',
                'active',
                'suspended',
                'revoked',
                'declined',
                'cancelled',
                'expired',
            ])->default('pending')->change();
            $table->timestamp('suspended_at')->nullable()->after('cancelled_at');
            $table->timestamp('revoked_at')->nullable()->after('suspended_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('workspace_beneficiaries')
            ->where('status', 'suspended')
            ->update(['status' => 'active']);
        DB::table('workspace_beneficiaries')
            ->where('status', 'revoked')
            ->update(['status' => 'cancelled']);

        Schema::table('workspace_beneficiaries', function (Blueprint $table) {
            $table->enum('status', [
                'pending',
                'active',
                'declined',
                'cancelled',
                'expired',
            ])->default('pending')->change();
            $table->dropColumn(['suspended_at', 'revoked_at']);
        });
    }
};
