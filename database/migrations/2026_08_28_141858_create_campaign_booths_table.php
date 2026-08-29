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
        Schema::create('campaign_booths', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('site');
            $table->string('community');
            $table->unsignedInteger('expected_beneficiaries')->nullable();
            $table->string('contact_name');
            $table->string('contact_phone', 32);
            $table->date('preferred_deployment_date');
            $table->decimal('setup_fee', 16, 2);
            $table->decimal('monthly_fee', 16, 2);
            $table->char('currency', 3)->default('NGN');
            $table->string('status', 24)->default('requested');
            $table->string('setup_reference')->nullable();
            $table->timestamp('setup_paid_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->date('paid_through')->nullable();
            $table->timestamp('last_billed_at')->nullable();
            $table->timestamps();

            $table->index(['campaign_id', 'status']);
            $table->index(['workspace_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_booths');
    }
};
