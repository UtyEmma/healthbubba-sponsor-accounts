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
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workspace_beneficiary_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('beneficiary_id');
            $table->unsignedInteger('doctor_id');
            $table->unsignedInteger('appointment_id')->nullable()->unique();
            $table->string('consultation_type');
            $table->string('feature_slug');
            $table->string('status');
            $table->string('allocation_scope');
            $table->string('plan_name');
            $table->unsignedInteger('allocation_limit')->nullable();
            $table->timestamp('allocation_period_start');
            $table->timestamp('allocation_period_end');
            $table->timestamp('reserved_at');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(
                ['workspace_id', 'status', 'consultation_type', 'allocation_period_start'],
                'consultations_workspace_usage_index',
            );
            $table->index(
                ['workspace_id', 'workspace_beneficiary_id', 'consultation_type', 'status'],
                'consultations_beneficiary_usage_index',
            );
            $table->index(['workspace_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultations');
    }
};
