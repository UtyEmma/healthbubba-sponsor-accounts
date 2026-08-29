<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_enrollment_codes', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('code')->unique();
            $table->unsignedInteger('enrollment_limit');
            $table->date('expires_at')->index();
            $table->timestamps();
            $table->index(['campaign_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_enrollment_codes');
    }
};
