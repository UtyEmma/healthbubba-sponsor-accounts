<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_attribution_events', function (Blueprint $table): void {
            $table->id();
            $table->uuid('idempotency_key')->unique();
            $table->foreignId('workspace_id')->constrained()->restrictOnDelete();
            $table->uuid('previous_referrer_id')->nullable();
            $table->uuid('referrer_id');
            $table->uuid('actor_id');
            $table->text('reason');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_attribution_events');
    }
};
