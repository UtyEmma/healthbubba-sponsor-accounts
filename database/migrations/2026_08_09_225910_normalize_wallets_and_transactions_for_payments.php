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
        Schema::table('wallets', function (Blueprint $table): void {
            $table->decimal('balance', 16, 2)->default(0)->change();
            $table->char('currency', 3)->default('NGN')->after('balance');

            $table->dropIndex(['owner_type', 'owner_id']);
            $table->unique(['owner_type', 'owner_id']);
        });

        Schema::table('transactions', function (Blueprint $table): void {
            $table->foreignId('payment_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->nullOnDelete();
            $table->decimal('amount', 16, 2)->change();
            $table->char('currency', 3)->default('NGN')->after('amount');

            $table->unique('payment_id');
            $table->unique('reference');
            $table->index(['status', 'created_at']);
            $table->index(['type', 'created_at']);
        });

        if (! Schema::hasColumn('transactions', 'meta')) {
            Schema::table('transactions', function (Blueprint $table): void {
                $table->json('meta')->nullable()->after('flow');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropIndex(['status', 'created_at']);
            $table->dropIndex(['type', 'created_at']);
            $table->dropUnique(['reference']);
            $table->dropUnique(['payment_id']);
            $table->dropConstrainedForeignId('payment_id');
            $table->dropColumn('currency');
            $table->double('amount')->change();
        });

        Schema::table('wallets', function (Blueprint $table): void {
            $table->dropUnique(['owner_type', 'owner_id']);
            $table->index(['owner_type', 'owner_id']);
            $table->dropColumn('currency');
            $table->double('balance')->change();
        });

        // `meta` is retained because it may have existed before this migration.
    }
};
