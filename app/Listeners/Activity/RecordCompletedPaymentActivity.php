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
        [$type, $title, $subjectType, $subjectId, $subjectName] = $this->presentation($payment);

        $this->activities->record($payment->workspace, new WorkspaceActivityData(
            type: $type,
            title: $title,
            actor: $actor,
            subjectType: $subjectType,
            subjectId: $subjectId,
            subjectName: $subjectName,
            description: sprintf('%s %s', $payment->currency, number_format($payment->amount_minor / 100, 2)),
            context: [
                'amount_minor' => $payment->amount_minor,
                'currency' => $payment->currency,
            ],
        ));
    }

    /** @return array{WorkspaceActivityType, string, string, int|string|null, string} */
    private function presentation(Payment $payment): array
    {
        return match ($payment->purpose) {
            PaymentPurpose::WALLET_TOP_UP => [
                WorkspaceActivityType::WalletTopUpCompleted,
                'Wallet funded successfully',
                'wallet',
                $payment->payable_id,
                'Workspace wallet',
            ],
            PaymentPurpose::CAPACITY_PURCHASE => $this->capacityPurchase($payment),
            PaymentPurpose::PLAN_UPGRADE => $this->planUpgrade($payment),
            PaymentPurpose::SUBSCRIPTION => $this->subscriptionPayment($payment),
        };
    }

    /** @return array{WorkspaceActivityType, string, string, int|string|null, string} */
    private function capacityPurchase(Payment $payment): array
    {
        $purchase = $payment->payable;
        $quantity = $purchase instanceof CapacityPurchase ? $purchase->quantity : 0;
        $unit = $payment->workspace->type->value === 'business' ? 'employee seat' : 'beneficiary slot';
        $subjectName = $quantity === 1 ? $unit : "{$unit}s";

        return [
            WorkspaceActivityType::CapacityPurchased,
            "Purchased {$quantity} additional {$subjectName}",
            'capacity_purchase',
            $payment->payable_id,
            $subjectName,
        ];
    }

    /** @return array{WorkspaceActivityType, string, string, int|string|null, string} */
    private function planUpgrade(Payment $payment): array
    {
        $targetPlanId = $payment->metadata['to_plan_id'] ?? null;
        $planName = is_int($targetPlanId) || (is_string($targetPlanId) && ctype_digit($targetPlanId))
            ? Plan::query()->whereKey((int) $targetPlanId)->value('name')
            : null;
        $name = is_string($planName) ? $planName : 'new plan';

        return [
            WorkspaceActivityType::PlanUpgradeCompleted,
            "Upgraded subscription to {$name}",
            'subscription',
            $payment->payable_id,
            $name,
        ];
    }

    /** @return array{WorkspaceActivityType, string, string, int|string|null, string} */
    private function subscriptionPayment(Payment $payment): array
    {
        if ($payment->payable instanceof Plan) {
            return [
                WorkspaceActivityType::SubscriptionActivated,
                "Activated {$payment->payable->name} subscription",
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
                "Downgraded subscription to {$planName} at renewal",
                'subscription',
                $payment->payable_id,
                $planName,
            ]
            : [
                WorkspaceActivityType::SubscriptionRenewed,
                "Renewed {$planName} subscription",
                'subscription',
                $payment->payable_id,
                $planName,
            ];
    }
}
