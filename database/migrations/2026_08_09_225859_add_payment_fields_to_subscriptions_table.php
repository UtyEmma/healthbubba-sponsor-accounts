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
            $table->foreignId('payment_method_id')
                ->nullable()
                ->after('plan_id')
                ->constrained()
                ->nullOnDelete();
            $table->string('gateway')->nullable()->after('payment_method_id');
            $table->unsignedInteger('seat_count')->default(1)->after('gateway');
            $table->boolean('auto_renew')->default(false)->after('seat_count');
            $table->timestamp('next_charge_at')->nullable()->index()->after('auto_renew');
            $table->unsignedTinyInteger('renewal_attempts')->default(0)->after('next_charge_at');
            $table->timestamp('renewal_retry_at')->nullable()->index()->after('renewal_attempts');
            $table->timestamp('recurring_consent_at')->nullable()->after('renewal_retry_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex(['next_charge_at']);
            $table->dropIndex(['renewal_retry_at']);
            $table->dropConstrainedForeignId('payment_method_id');
            $table->dropColumn([
                'gateway',
                'seat_count',
                'auto_renew',
                'next_charge_at',
                'renewal_attempts',
                'renewal_retry_at',
                'recurring_consent_at',
            ]);
        });
    }
};
