<?php

namespace App\Actions\Payments;

use App\Enums\CapacityPurchases\CapacityPurchaseStatus;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentStatus;
use App\Models\CapacityPurchase;
use App\Models\Payment;
use Illuminate\Support\Str;

final class FailPaymentAction
{
    public function execute(Payment $payment, string $code, string $message): void
    {
        Payment::query()
            ->whereKey($payment->getKey())
            ->where('status', '!=', PaymentStatus::SUCCEEDED)
            ->update([
                'status' => PaymentStatus::FAILED,
                'failure_code' => Str::limit($code, 100, ''),
                'failure_message' => Str::limit($message, 500, ''),
                'failed_at' => now(),
            ]);

        if ($payment->purpose === PaymentPurpose::CAPACITY_PURCHASE
            && $payment->payable_type === (new CapacityPurchase)->getMorphClass()) {
            CapacityPurchase::query()
                ->whereKey($payment->payable_id)
                ->where('status', CapacityPurchaseStatus::PENDING)
                ->update([
                    'status' => CapacityPurchaseStatus::FAILED,
                    'failure_message' => Str::limit($message, 500, ''),
                ]);
        }
    }
}
