<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_booth_charges', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->date('service_period');
            $table->unsignedInteger('quantity');
            $table->decimal('unit_fee', 16, 2);
            $table->decimal('total_cost', 16, 2);
            $table->char('currency', 3)->default('NGN');
            $table->string('status', 24);
            $table->string('reference')->nullable()->unique();
            $table->timestamp('attempted_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['campaign_id', 'service_period']);
            $table->index(['status', 'service_period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_booth_charges');
    }
};
