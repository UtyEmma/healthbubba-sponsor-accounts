<?php

namespace App\Actions\Payments;

use App\DTOs\Activity\WorkspaceActivityActor;
use App\DTOs\Activity\WorkspaceActivityData;
use App\Enums\Activity\WorkspaceActivityType;
use App\Enums\Payments\PaymentStatus;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Workspace;
use App\Services\Activity\WorkspaceActivityLogger;
use Illuminate\Support\Facades\DB;
use Revoltify\Subscriptionify\Enums\SubscriptionStatus;

final readonly class RecordRenewalFailureAction
{
    /** @var array<int, int> */
    private const RETRY_HOURS = [
        1 => 24,
        2 => 72,
        3 => 120,
    ];

    public function __construct(private WorkspaceActivityLogger $activities) {}

    public function execute(Subscription $subscription, ?Payment $payment = null): void
    {
        DB::transaction(function () use ($subscription, $payment): void {
            $lockedPayment = $payment instanceof Payment
                ? Payment::query()->whereKey($payment->getKey())->lockForUpdate()->firstOrFail()
                : null;

            if ($lockedPayment instanceof Payment
                && ($lockedPayment->status !== PaymentStatus::FAILED
                    || $lockedPayment->payable_type !== $subscription->getMorphClass()
                    || (int) $lockedPayment->payable_id !== (int) $subscription->getKey())) {
                return;
            }

            $lockedSubscription = Subscription::query()
                ->whereKey($subscription->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedSubscription->auto_renew
                || $lockedSubscription->status !== SubscriptionStatus::Active) {
                return;
            }

            if ($lockedPayment instanceof Payment
                && $this->renewalAttempt($lockedPayment) !== $lockedSubscription->renewal_attempts + 1) {
                return;
            }

            if (! $this->isStillDue($lockedSubscription, $lockedPayment)) {
                return;
            }

            $attempts = $lockedSubscription->renewal_attempts + 1;
            $retryHours = self::RETRY_HOURS[$attempts] ?? null;

            if ($retryHours === null) {
                $lockedSubscription->update([
                    'status' => SubscriptionStatus::PastDue,
                    'auto_renew' => false,
                    'next_charge_at' => null,
                    'renewal_attempts' => $attempts,
                    'renewal_retry_at' => null,
                ]);

                $workspace = Workspace::query()->find($lockedSubscription->subscribable_id);

                if ($workspace instanceof Workspace) {
                    $planName = $lockedSubscription->plan()->value('name') ?? 'subscription';
                    $this->activities->record($workspace, new WorkspaceActivityData(
                        type: WorkspaceActivityType::SubscriptionPastDue,
                        title: 'Subscription moved to past due',
                        actor: WorkspaceActivityActor::system(),
                        subjectType: 'subscription',
                        subjectId: $lockedSubscription->getKey(),
                        subjectName: $planName,
                        description: 'Renewal attempts were exhausted.',
                        context: ['plan_name' => $planName],
                    ));
                }

                return;
            }

            $renewalDueAt = $lockedSubscription->next_charge_at ?? now();
            $retryAt = $renewalDueAt->copy()->addHours($retryHours);

            if ($retryAt->isPast()) {
                $retryAt = now()->addMinutes(15);
            }

            $lockedSubscription->update([
                'renewal_attempts' => $attempts,
                'renewal_retry_at' => $retryAt,
            ]);
        }, 3);
    }

    private function renewalAttempt(Payment $payment): ?int
    {
        $attempt = $payment->metadata['renewal_attempt'] ?? null;

        if (is_int($attempt) && $attempt > 0) {
            return $attempt;
        }

        if (is_string($attempt) && ctype_digit($attempt) && (int) $attempt > 0) {
            return (int) $attempt;
        }

        return null;
    }

    private function isStillDue(Subscription $subscription, ?Payment $payment): bool
    {
        if ($subscription->renewal_retry_at !== null) {
            $isDue = $subscription->renewal_retry_at->isPast();
        } else {
            $isDue = $subscription->next_charge_at?->isPast() === true;
        }

        if (! $isDue || ! $payment instanceof Payment) {
            return $isDue;
        }

        $paymentDueAt = $payment->metadata['renewal_due_at'] ?? null;

        return ! is_string($paymentDueAt)
            || $subscription->next_charge_at?->toISOString() === $paymentDueAt;
    }
}
