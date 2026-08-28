<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_budget_usages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('category', 32);
            $table->decimal('amount', 16, 2);
            $table->char('currency', 3)->default('NGN');
            $table->string('reference')->unique();
            $table->timestamp('occurred_at');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['campaign_id', 'category', 'occurred_at']);
            $table->index(['workspace_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_budget_usages');
    }
};
