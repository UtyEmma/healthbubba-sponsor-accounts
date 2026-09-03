<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table): void {
            $table->text('description')->nullable()->after('name');
            $table->string('enrollment_method', 32)->nullable()->after('target_audience');
            $table->unsignedInteger('estimated_beneficiaries')->nullable()->after('enrollment_method');
            $table->char('currency', 3)->default('NGN')->after('specialist_fee');
            $table->decimal('medication_budget', 16, 2)->default(0)->after('currency');
            $table->decimal('laboratory_budget', 16, 2)->default(0)->after('medication_budget');
            $table->string('allocation_reference')->nullable()->unique()->after('laboratory_budget');
            $table->decimal('returned_amount', 16, 2)->default(0)->after('allocation_reference');
            $table->timestamp('launched_at')->nullable()->after('returned_amount');
            $table->timestamp('paused_at')->nullable()->after('launched_at');
            $table->timestamp('ended_at')->nullable()->after('paused_at');
            $table->unsignedInteger('booth_count')->nullable()->after('booth_required');
            $table->date('booth_preferred_deployment_date')->nullable()->after('booth_count');
            $table->string('booth_site')->nullable()->after('booth_preferred_deployment_date');
            $table->string('booth_contact_name')->nullable()->after('booth_site');
            $table->string('booth_contact_phone', 32)->nullable()->after('booth_contact_name');
            $table->decimal('booth_setup_unit_fee', 16, 2)->nullable()->after('booth_contact_phone');
            $table->decimal('booth_monthly_unit_fee', 16, 2)->nullable()->after('booth_setup_unit_fee');
            $table->timestamp('booth_activated_at')->nullable()->after('booth_monthly_unit_fee');
            $table->timestamp('booth_deactivated_at')->nullable()->after('booth_activated_at');
            $table->timestamp('booth_last_billed_at')->nullable()->after('booth_deactivated_at');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table): void {
            $table->dropUnique(['allocation_reference']);
            $table->dropColumn([
                'description',
                'enrollment_method',
                'estimated_beneficiaries',
                'currency',
                'medication_budget',
                'laboratory_budget',
                'allocation_reference',
                'returned_amount',
                'launched_at',
                'paused_at',
                'ended_at',
                'booth_count',
                'booth_preferred_deployment_date',
                'booth_site',
                'booth_contact_name',
                'booth_contact_phone',
                'booth_setup_unit_fee',
                'booth_monthly_unit_fee',
                'booth_activated_at',
                'booth_deactivated_at',
                'booth_last_billed_at',
            ]);
        });
    }
};
