<?php

namespace App\Actions\Payments;

use App\DTOs\Activity\WorkspaceActivityActor;
use App\DTOs\Activity\WorkspaceActivityData;
use App\Enums\Activity\WorkspaceActivityType;
use App\Enums\CapacityPurchases\CapacityPurchaseStatus;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentStatus;
use App\Models\CapacityPurchase;
use App\Models\Payment;
use App\Models\User;
use App\Services\Activity\WorkspaceActivityLogger;
use Illuminate\Support\Str;

final readonly class FailPaymentAction
{
    public function __construct(private WorkspaceActivityLogger $activities) {}

    public function execute(Payment $payment, string $code, string $message): void
    {
        $updated = Payment::query()
            ->whereKey($payment->getKey())
            ->whereNotIn('status', [PaymentStatus::SUCCEEDED, PaymentStatus::FAILED])
            ->update([
                'status' => PaymentStatus::FAILED,
                'failure_code' => Str::limit($code, 100, ''),
                'failure_message' => Str::limit($message, 500, ''),
                'failed_at' => now(),
            ]);

        if ($updated === 0) {
            return;
        }

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

        $payment->refresh()->loadMissing(['workspace', 'initiator']);

        if (isset($payment->metadata['renewal_attempt'])) {
            return;
        }

        $actor = $payment->initiator instanceof User
            ? WorkspaceActivityActor::user($payment->initiator)
            : WorkspaceActivityActor::system();
        $amount = ($payment->currency === 'NGN' ? '₦' : "{$payment->currency} ")
            .number_format($payment->amount_minor / 100, 2);
        $purpose = match ($payment->purpose) {
            PaymentPurpose::WALLET_TOP_UP => 'Wallet funding',
            PaymentPurpose::SUBSCRIPTION => 'Subscription payment',
            PaymentPurpose::CAPACITY_PURCHASE => 'Capacity purchase',
            PaymentPurpose::PLAN_UPGRADE => 'Plan upgrade payment',
        };

        $this->activities->record($payment->workspace, new WorkspaceActivityData(
            type: WorkspaceActivityType::PaymentFailed,
            title: "{$purpose} of {$amount} failed",
            actor: $actor,
            subjectType: 'payment',
            subjectId: $payment->getKey(),
            subjectName: $payment->purpose->value,
            context: [
                'purpose' => $payment->purpose->value,
                'amount_minor' => $payment->amount_minor,
                'currency' => $payment->currency,
            ],
        ));
    }
}
