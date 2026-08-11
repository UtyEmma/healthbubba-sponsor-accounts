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
        Schema::create('capacity_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference')->unique();
            $table->string('payment_source');
            $table->string('status')->default('pending');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('previous_capacity');
            $table->unsignedInteger('new_capacity');
            $table->unsignedBigInteger('unit_amount_minor');
            $table->unsignedBigInteger('prorated_unit_amount_minor');
            $table->unsignedBigInteger('amount_minor');
            $table->unsignedBigInteger('renewal_amount_minor');
            $table->char('currency', 3)->default('NGN');
            $table->timestamp('term_starts_at');
            $table->timestamp('term_ends_at');
            $table->text('failure_message')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['subscription_id', 'status', 'created_at']);
            $table->index(['workspace_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('capacity_purchases');
    }
};
