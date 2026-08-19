<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_consultation_quotas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('consultation_type');
            $table->unsignedInteger('quantity');
            $table->decimal('unit_fee', 16, 2);
            $table->decimal('total_cost', 16, 2);
            $table->string('reference')->unique();
            $table->timestamps();

            $table->index(['campaign_id', 'consultation_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_consultation_quotas');
    }
};
