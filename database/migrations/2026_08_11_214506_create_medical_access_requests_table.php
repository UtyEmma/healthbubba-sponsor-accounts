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
        Schema::create('medical_access_requests', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workspace_beneficiary_id')
                ->constrained('workspace_beneficiaries')
                ->cascadeOnDelete();
            $table->foreignId('requested_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->enum('data_type', [
                'CLINICAL_RECORD',
                'PRESCRIPTION_RECORD',
                'LAB_RECORD',
            ]);
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'denied', 'expired'])
                ->default('pending');
            $table->timestamp('requested_at');
            $table->timestamp('review_expires_at');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('denied_at')->nullable();
            $table->timestamp('access_expires_at')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'created_at'], 'medical_access_workspace_history_index');
            $table->index(['status', 'review_expires_at'], 'medical_access_review_expiry_index');
            $table->index(['status', 'access_expires_at'], 'medical_access_grant_expiry_index');
            $table->index(
                ['workspace_beneficiary_id', 'data_type', 'status'],
                'medical_access_beneficiary_type_status_index',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_access_requests');
    }
};
