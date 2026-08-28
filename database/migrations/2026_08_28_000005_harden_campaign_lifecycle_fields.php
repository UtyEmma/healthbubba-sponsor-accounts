<?php

use App\Enums\CampaignStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table): void {
            $table->string('status')->default(CampaignStatus::PENDING->value)->nullable(false)->change();
            $table->unsignedInteger('beneficiary_limit')->nullable()->default(null)->change();
            $table->index(['workspace_id', 'status', 'start_date', 'end_date'], 'campaigns_workspace_lifecycle_index');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table): void {
            $table->dropIndex('campaigns_workspace_lifecycle_index');
            $table->unsignedInteger('beneficiary_limit')->default(100)->nullable(false)->change();
            $table->string('status')->nullable()->default(null)->change();
        });
    }
};
