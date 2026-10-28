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
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('funding_source')
                ->default('customer_paid')
                ->after('gateway')
                ->index();
            $table->foreignId('assigned_by_user_id')
                ->nullable()
                ->after('funding_source')
                ->constrained('users')
                ->nullOnDelete();
            $table->boolean('complimentary_renews')
                ->default(false)
                ->after('assigned_by_user_id')
                ->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex(['funding_source']);
            $table->dropIndex(['complimentary_renews']);
            $table->dropConstrainedForeignId('assigned_by_user_id');
            $table->dropColumn(['funding_source', 'complimentary_renews']);
        });
    }
};
