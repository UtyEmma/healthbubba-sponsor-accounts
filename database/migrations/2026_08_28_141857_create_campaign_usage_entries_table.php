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
        Schema::create('campaign_usage_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workspace_beneficiary_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('benefit', 32);
            $table->unsignedInteger('quantity')->nullable();
            $table->decimal('unit_amount', 16, 2)->nullable();
            $table->decimal('total_amount', 16, 2);
            $table->char('currency', 3)->default('NGN');
            $table->string('source', 24);
            $table->string('source_reference')->nullable()->unique();
            $table->string('reference')->unique();
            $table->timestamp('occurred_at');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['campaign_id', 'benefit', 'occurred_at']);
            $table->index(['workspace_id', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_usage_entries');
    }
};
