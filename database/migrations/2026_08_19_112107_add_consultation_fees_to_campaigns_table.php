<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table): void {
            $table->decimal('gp_fee', 16, 2)->nullable()->default(1000)->after('booth_required');
            $table->decimal('specialist_fee', 16, 2)->nullable()->default(1000)->after('gp_fee');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table): void {
            $table->dropColumn(['gp_fee', 'specialist_fee']);
        });
    }
};
