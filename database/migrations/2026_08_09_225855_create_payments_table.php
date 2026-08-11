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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->nullableMorphs('payable');
            $table->foreignId('payment_method_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->string('purpose');
            $table->string('gateway');
            $table->string('status')->default('pending');
            $table->string('reference')->unique();
            $table->string('provider_reference')->nullable()->index();
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3)->default('NGN');
            $table->json('metadata')->nullable();
            $table->json('provider_metadata')->nullable();
            $table->string('failure_code')->nullable();
            $table->text('failure_message')->nullable();
            $table->timestamp('initialized_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'status', 'created_at']);
            $table->index(['purpose', 'status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
