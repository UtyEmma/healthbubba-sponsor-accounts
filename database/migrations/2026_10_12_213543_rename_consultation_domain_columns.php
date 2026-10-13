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
        Schema::table('campaigns', function (Blueprint $table): void {
            $table->renameColumn('gp_fee', 'scheduled_consultation_fee');
            $table->renameColumn('specialist_fee', 'instant_consultation_fee');
            $table->renameColumn(
                'gp_limit_per_beneficiary_override',
                'scheduled_consultation_limit_per_beneficiary_override',
            );
            $table->renameColumn(
                'specialist_limit_per_beneficiary_override',
                'instant_consultation_limit_per_beneficiary_override',
            );
        });

        Schema::table('institutional_funding_programs', function (Blueprint $table): void {
            $table->renameColumn(
                'gp_limit_per_beneficiary',
                'scheduled_consultation_limit_per_beneficiary',
            );
            $table->renameColumn(
                'specialist_limit_per_beneficiary',
                'instant_consultation_limit_per_beneficiary',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('institutional_funding_programs', function (Blueprint $table): void {
            $table->renameColumn(
                'scheduled_consultation_limit_per_beneficiary',
                'gp_limit_per_beneficiary',
            );
            $table->renameColumn(
                'instant_consultation_limit_per_beneficiary',
                'specialist_limit_per_beneficiary',
            );
        });

        Schema::table('campaigns', function (Blueprint $table): void {
            $table->renameColumn('scheduled_consultation_fee', 'gp_fee');
            $table->renameColumn('instant_consultation_fee', 'specialist_fee');
            $table->renameColumn(
                'scheduled_consultation_limit_per_beneficiary_override',
                'gp_limit_per_beneficiary_override',
            );
            $table->renameColumn(
                'instant_consultation_limit_per_beneficiary_override',
                'specialist_limit_per_beneficiary_override',
            );
        });
    }
};
