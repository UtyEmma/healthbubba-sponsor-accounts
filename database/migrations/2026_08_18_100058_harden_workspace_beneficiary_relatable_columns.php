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
        if (Schema::hasForeignKey(
            'workspace_beneficiaries',
            'workspace_beneficiaries_campaign_id_foreign',
        )) {
            Schema::table('workspace_beneficiaries', function (Blueprint $table): void {
                $table->dropForeign('workspace_beneficiaries_campaign_id_foreign');
            });
        }

        Schema::whenTableHasIndex(
            'workspace_beneficiaries',
            'workspace_beneficiaries_workspace_id_email_unique',
            function (Blueprint $table): void {
                $table->dropUnique('workspace_beneficiaries_workspace_id_email_unique');
            },
        );

        Schema::whenTableHasIndex(
            'workspace_beneficiaries',
            'workspace_beneficiaries_campaign_status_index',
            function (Blueprint $table): void {
                $table->dropIndex('workspace_beneficiaries_campaign_status_index');
            },
        );

        Schema::whenTableHasColumn(
            'workspace_beneficiaries',
            'campaign_id',
            function (Blueprint $table): void {
                $table->dropColumn('campaign_id');
            },
        );

        Schema::table('workspace_beneficiaries', function (Blueprint $table): void {
            $table->string('relatable_type')->nullable(false)->change();
            $table->unsignedBigInteger('relatable_id')->nullable(false)->change();
        });

        Schema::whenTableDoesntHaveIndex(
            'workspace_beneficiaries',
            'workspace_beneficiaries_relatable_email_unique',
            function (Blueprint $table): void {
                $table->unique(
                    ['workspace_id', 'relatable_type', 'relatable_id', 'email'],
                    'workspace_beneficiaries_relatable_email_unique',
                );
            },
        );

        Schema::whenTableDoesntHaveIndex(
            'workspace_beneficiaries',
            'workspace_beneficiaries_relatable_capacity_index',
            function (Blueprint $table): void {
                $table->index(
                    ['relatable_type', 'relatable_id', 'status', 'expires_at'],
                    'workspace_beneficiaries_relatable_capacity_index',
                );
            },
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        throw new LogicException(
            'This migration is intentionally forward-only because the legacy workspace/email uniqueness cannot represent multiple campaign enrollments.',
        );
    }
};
