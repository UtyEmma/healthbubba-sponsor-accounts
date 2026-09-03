<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workspaces', function (Blueprint $table): void {
            $table->string('organization_type')->nullable()->after('type');
            $table->char('country_code', 2)->nullable()->after('organization_type');
            $table->string('state_code', 8)->nullable()->after('country_code');
            $table->string('official_email')->nullable()->after('state_code');
            $table->string('official_phone', 32)->nullable()->after('official_email');
        });
    }

    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table): void {
            $table->dropColumn([
                'organization_type',
                'country_code',
                'state_code',
                'official_email',
                'official_phone',
            ]);
        });
    }
};
