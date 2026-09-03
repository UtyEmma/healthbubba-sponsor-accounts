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
        Schema::create('campaign_recurring_cost_charges', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campaign_recurring_cost_id')->constrained(null, null, 'campgn_rec_cost_id')->cascadeOnDelete();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->date('service_period');
            $table->decimal('amount', 16, 2);
            $table->char('currency', 3)->default('NGN');
            $table->string('status', 24)->default('pending');
            $table->string('reference')->nullable()->unique();
            $table->timestamp('attempted_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            // $table->unique(['campaign_recurring_cost_id', 'service_period'], 'campaign_cost_period_unique');
            // $table->index(['status', 'service_period']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_recurring_cost_charges');
    }
};
