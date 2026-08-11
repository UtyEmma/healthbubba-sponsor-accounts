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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('gateway');
            $table->string('type')->default('card');
            $table->text('authorization_code');
            $table->char('authorization_hash', 64);
            $table->text('customer_code')->nullable();
            $table->text('email');
            $table->string('brand')->nullable();
            $table->char('last_four', 4)->nullable();
            $table->char('exp_month', 2)->nullable();
            $table->char('exp_year', 4)->nullable();
            $table->boolean('reusable')->default(false);
            $table->boolean('is_default')->default(false);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['workspace_id', 'gateway', 'authorization_hash'],
                'payment_methods_workspace_gateway_authorization_unique',
            );
            $table->index(['workspace_id', 'is_default']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
