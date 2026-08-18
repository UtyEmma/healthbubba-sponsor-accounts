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
        if (! Schema::hasColumn('workspaces', 'onboarded_at')) {
            Schema::table('workspaces', function (Blueprint $table): void {
                $table->timestamp('onboarded_at')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // The canonical onboarding migration owns this column on fresh databases.
    }
};
