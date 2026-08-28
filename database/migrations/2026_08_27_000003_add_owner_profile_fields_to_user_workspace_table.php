<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_workspace', function (Blueprint $table): void {
            $table->string('phone', 32)->nullable()->after('email');
            $table->string('job_title')->nullable()->after('phone');
            $table->timestamp('authorization_confirmed_at')->nullable()->after('job_title');
        });
    }

    public function down(): void
    {
        Schema::table('user_workspace', function (Blueprint $table): void {
            $table->dropColumn([
                'phone',
                'job_title',
                'authorization_confirmed_at',
            ]);
        });
    }
};
