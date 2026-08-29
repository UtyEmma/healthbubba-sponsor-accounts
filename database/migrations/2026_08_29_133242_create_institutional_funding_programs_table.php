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
        Schema::create('institutional_funding_programs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('coverage_type', 32)->default('shared_pool');
            $table->unsignedSmallInteger('gp_limit_per_beneficiary')->default(4);
            $table->unsignedSmallInteger('specialist_limit_per_beneficiary')->default(2);
            $table->unsignedSmallInteger('daily_consultation_limit')->default(1);
            $table->string('expiry_cadence', 24)->default('annual');
            $table->string('payment_preference', 32)->default('user_choice');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('institutional_funding_programs');
    }
};
