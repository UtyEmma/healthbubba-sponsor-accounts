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
            $table->string('display_enrollment_code')->nullable()->unique()->after('allocation_reference');
        });

        Schema::table('workspace_beneficiaries', function (Blueprint $table): void {
            $table->foreignId('campaign_booth_id')->nullable()->after('relatable_id')->constrained()->nullOnDelete();
            $table->string('community')->nullable()->after('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workspace_beneficiaries', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('campaign_booth_id');
            $table->dropColumn('community');
        });

        Schema::table('campaigns', function (Blueprint $table): void {
            $table->dropUnique(['display_enrollment_code']);
            $table->dropColumn('display_enrollment_code');
        });
    }
};
