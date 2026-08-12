<?php

namespace App\Listeners\Activity;

use App\DTOs\Activity\WorkspaceActivityActor;
use App\DTOs\Activity\WorkspaceActivityData;
use App\Enums\Activity\WorkspaceActivityType;
use App\Enums\Payments\PaymentPurpose;
use App\Events\Payments\PaymentCompleted;
use App\Models\CapacityPurchase;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Activity\WorkspaceActivityLogger;

final readonly class RecordCompletedPaymentActivity
{
    public function __construct(private WorkspaceActivityLogger $activities) {}

    public function handle(PaymentCompleted $event): void
    {
        $payment = Payment::query()
            ->with(['workspace', 'initiator', 'payable'])
            ->find($event->payment->getKey());

        if (! $payment instanceof Payment) {
            return;
        }

        $actor = $payment->initiator instanceof User
            ? WorkspaceActivityActor::user($payment->initiator)
            : WorkspaceActivityActor::system();
        $amount = $this->formattedAmount($payment);
        [$type, $title, $subjectType, $subjectId, $subjectName] = $this->presentation($payment, $amount);

        $this->activities->record($payment->workspace, new WorkspaceActivityData(
            type: $type,
            title: $title,
            actor: $actor,
            subjectType: $subjectType,
            subjectId: $subjectId,
            subjectName: $subjectName,
            context: [
                'amount_minor' => $payment->amount_minor,
                'currency' => $payment->currency,
            ],
        ));
    }

    /** @return array{WorkspaceActivityType, string, string, int|string|null, string} */
    private function presentation(Payment $payment, string $amount): array
    {
        return match ($payment->purpose) {
            PaymentPurpose::WALLET_TOP_UP => [
                WorkspaceActivityType::WalletTopUpCompleted,
                "Funded wallet with {$amount}",
                'wallet',
                $payment->payable_id,
                'Workspace wallet',
            ],
            PaymentPurpose::CAPACITY_PURCHASE => $this->capacityPurchase($payment, $amount),
            PaymentPurpose::PLAN_UPGRADE => $this->planUpgrade($payment, $amount),
            PaymentPurpose::SUBSCRIPTION => $this->subscriptionPayment($payment, $amount),
        };
    }

    /** @return array{WorkspaceActivityType, string, string, int|string|null, string} */
    private function capacityPurchase(Payment $payment, string $amount): array
    {
        $purchase = $payment->payable;
        $quantity = $purchase instanceof CapacityPurchase ? $purchase->quantity : 0;
        $unit = $payment->workspace->type->value === 'business' ? 'employee seat' : 'beneficiary slot';
        $subjectName = $quantity === 1 ? $unit : "{$unit}s";

        return [
            WorkspaceActivityType::CapacityPurchased,
            "Purchased {$quantity} additional {$subjectName} for {$amount}",
            'capacity_purchase',
            $payment->payable_id,
            $subjectName,
        ];
    }

    /** @return array{WorkspaceActivityType, string, string, int|string|null, string} */
    private function planUpgrade(Payment $payment, string $amount): array
    {
        $targetPlanId = $payment->metadata['to_plan_id'] ?? null;
        $planName = is_int($targetPlanId) || (is_string($targetPlanId) && ctype_digit($targetPlanId))
            ? Plan::query()->whereKey((int) $targetPlanId)->value('name')
            : null;
        $name = is_string($planName) ? $planName : 'new plan';

        return [
            WorkspaceActivityType::PlanUpgradeCompleted,
            "Upgraded subscription to {$name} for {$amount}",
            'subscription',
            $payment->payable_id,
            $name,
        ];
    }

    /** @return array{WorkspaceActivityType, string, string, int|string|null, string} */
    private function subscriptionPayment(Payment $payment, string $amount): array
    {
        if ($payment->payable instanceof Plan) {
            return [
                WorkspaceActivityType::SubscriptionActivated,
                "Activated {$payment->payable->name} subscription for {$amount}",
                'subscription',
                $payment->payable_id,
                $payment->payable->name,
            ];
        }

        $subscription = $payment->payable;
        $storedPlanName = $subscription instanceof Subscription
            ? Plan::query()->whereKey($subscription->plan_id)->value('name')
            : null;
        $planName = is_string($storedPlanName) ? $storedPlanName : 'current plan';
        $downgradeApplied = ($payment->metadata['scheduled_plan_change'] ?? false) === true;

        return $downgradeApplied
            ? [
                WorkspaceActivityType::PlanDowngradeApplied,
                "Downgraded subscription to {$planName} at renewal for {$amount}",
                'subscription',
                $payment->payable_id,
                $planName,
            ]
            : [
                WorkspaceActivityType::SubscriptionRenewed,
                "Renewed {$planName} subscription for {$amount}",
                'subscription',
                $payment->payable_id,
                $planName,
            ];
    }

    private function formattedAmount(Payment $payment): string
    {
        $currency = $payment->currency === 'NGN' ? '₦' : "{$payment->currency} ";

        return $currency.number_format($payment->amount_minor / 100, 2);
    }
}
