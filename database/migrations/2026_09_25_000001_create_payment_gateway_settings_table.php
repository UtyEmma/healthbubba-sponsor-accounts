<?php

use App\Enums\Payments\PaymentGatewayName;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateway_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('active_gateway');
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $gateway = PaymentGatewayName::tryFrom((string) config('payments.default'))
            ?? PaymentGatewayName::PAYSTACK;

        DB::table('payment_gateway_settings')->insert([
            'id' => 1,
            'active_gateway' => $gateway->value,
            'updated_by_user_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_settings');
    }
};
