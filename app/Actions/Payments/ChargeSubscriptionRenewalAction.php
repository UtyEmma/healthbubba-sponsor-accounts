<?php

namespace App\Actions\Payments;

use App\DTOs\Payments\RecurringChargeData;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentStatus;
use App\Exceptions\Payments\CheckoutUnavailable;
use App\Exceptions\Payments\GatewayRequestException;
use App\Exceptions\Payments\PaymentException;
use App\Exceptions\Payments\PaymentVerificationFailed;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Subscription;
use App\Models\Workspace;
use App\Payments\PaymentService;
use App\Services\Payments\PlanPricingService;
use App\Support\Payments\PaymentReferenceGenerator;
use App\ValueObjects\Money;
use Illuminate\Support\Facades\DB;
use Revoltify\Subscriptionify\Enums\SubscriptionStatus;

final readonly class ChargeSubscriptionRenewalAction
{
    public function __construct(
        private PaymentService $payments,
        private PaymentReferenceGenerator $references,
        private PlanPricingService $pricing,
        private CompletePaymentAction $completePayment,
        private FailPaymentAction $failPayment,
        private RecordRenewalFailureAction $recordFailure,
    ) {}

    public function execute(int $subscriptionId): void
    {
        $subscription = Subscription::query()
            ->with(['plan', 'paymentMethod'])
            ->findOrFail($subscriptionId);

        if (! $this->isDue($subscription)) {
            return;
        }

        $pendingPayment = $this->pendingPayment($subscription);

        if ($pendingPayment instanceof Payment) {
            $this->reconcilePendingPayment($subscription, $pendingPayment);

            return;
        }

        try {
            $payment = $this->createPayment($subscription);
        } catch (CheckoutUnavailable $exception) {
            report($exception);
            $this->recordFailure->execute($subscription);

            return;
        }

        if (! $payment instanceof Payment) {
            return;
        }

        if (! $payment->wasRecentlyCreated) {
            $this->reconcilePendingPayment($subscription, $payment);

            return;
        }

        $paymentMethod = $payment->payment_method_id === null
            ? null
            : PaymentMethod::query()
                ->whereKey($payment->payment_method_id)
                ->where('workspace_id', $payment->workspace_id)
                ->where('gateway', $payment->gateway)
                ->first();

        if (! $paymentMethod instanceof PaymentMethod || ! $paymentMethod->reusable) {
            $this->failPayment->execute($payment, 'missing_reusable_authorization', 'No reusable payment method is available.');
            $this->recordFailure->execute($subscription, $payment);

            return;
        }

        try {
            $verification = $this->payments->charge(
                new RecurringChargeData(
                    amount: new Money($payment->amount_minor, $payment->currency),
                    email: $paymentMethod->email,
                    authorizationCode: $paymentMethod->authorization_code,
                    reference: $payment->reference,
                    metadata: $payment->metadata,
                ),
                $payment->gateway,
            );

            $completedPayment = $this->completePayment->execute(
                reference: $payment->reference,
                verification: $verification,
                gateway: $payment->gateway,
            );
        } catch (PaymentException|PaymentVerificationFailed $exception) {
            if ($exception instanceof PaymentVerificationFailed) {
                report($exception);

                return;
            }

            if ($exception instanceof GatewayRequestException) {
                Payment::query()
                    ->whereKey($payment->getKey())
                    ->whereIn('status', [PaymentStatus::PENDING, PaymentStatus::PROCESSING])
                    ->update([
                        'status' => PaymentStatus::PROCESSING,
                        'failure_code' => 'verification_required',
                        'failure_message' => 'The charge result is awaiting server-side verification.',
                    ]);
                report($exception);

                return;
            }

            $this->failPayment->execute($payment, 'renewal_charge_failed', $exception->getMessage());
            $this->recordFailure->execute($subscription, $payment);

            return;
        }

        if ($completedPayment->status === PaymentStatus::FAILED) {
            $this->recordFailure->execute($subscription, $completedPayment);
        }
    }

    private function createPayment(Subscription $subscription): ?Payment
    {
        return DB::transaction(function () use ($subscription): ?Payment {
            $lockedSubscription = Subscription::query()
                ->with(['plan', 'paymentMethod'])
                ->whereKey($subscription->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $this->isDue($lockedSubscription)) {
                return null;
            }

            $existingPayment = $this->pendingPayment($lockedSubscription);

            if ($existingPayment instanceof Payment) {
                return $existingPayment;
            }

            $quote = $this->pricing->renewal($lockedSubscription);
            $paymentMethod = $lockedSubscription->paymentMethod;
            $email = $paymentMethod?->email;

            if (! $paymentMethod instanceof PaymentMethod
                || (int) $paymentMethod->workspace_id !== (int) $lockedSubscription->subscribable_id
                || $paymentMethod->gateway !== $lockedSubscription->gateway
                || ! is_string($email)
                || $email === '') {
                throw new CheckoutUnavailable('The subscription payment email is unavailable.');
            }

            $payment = Payment::query()->create([
                'workspace_id' => $lockedSubscription->subscribable_id,
                'user_id' => null,
                'payable_type' => $lockedSubscription->getMorphClass(),
                'payable_id' => $lockedSubscription->getKey(),
                'payment_method_id' => $lockedSubscription->payment_method_id,
                'purpose' => PaymentPurpose::SUBSCRIPTION,
                'status' => PaymentStatus::PENDING,
                'gateway' => $lockedSubscription->gateway,
                'reference' => $this->references->generate(PaymentPurpose::SUBSCRIPTION),
                'amount_minor' => $quote->money->amountInMinorUnits,
                'currency' => $quote->money->currency,
                'metadata' => [
                    'email' => $email,
                    'workspace_id' => $lockedSubscription->subscribable_id,
                    'purpose' => PaymentPurpose::SUBSCRIPTION->value,
                    'subscription_id' => $lockedSubscription->getKey(),
                    'plan_id' => $lockedSubscription->plan_id,
                    'capacity_count' => $quote->capacityCount,
                    'additional_capacity' => $quote->additionalCapacity,
                    'renewal_attempt' => $lockedSubscription->renewal_attempts + 1,
                    'renewal_due_at' => $lockedSubscription->next_charge_at?->toISOString(),
                ],
                'initialized_at' => now(),
            ]);

            $payment->update([
                'metadata' => [
                    ...$payment->metadata,
                    'payment_id' => $payment->getKey(),
                ],
            ]);

            return $payment;
        }, 3);
    }

    private function reconcilePendingPayment(Subscription $subscription, Payment $payment): void
    {
        if ($payment->status === PaymentStatus::REQUIRES_REVIEW) {
            return;
        }

        try {
            $completedPayment = $this->completePayment->execute(
                reference: $payment->reference,
                gateway: $payment->gateway,
            );
        } catch (PaymentException|PaymentVerificationFailed $exception) {
            report($exception);
            $this->quarantineUnknownOutcomeIfStale($payment);

            return;
        }

        if ($completedPayment->status === PaymentStatus::FAILED) {
            $this->recordFailure->execute($subscription, $completedPayment);

            return;
        }

        if (in_array($completedPayment->status, [PaymentStatus::PENDING, PaymentStatus::PROCESSING], true)) {
            $this->quarantineUnknownOutcomeIfStale($completedPayment);
        }
    }

    private function quarantineUnknownOutcomeIfStale(Payment $payment): void
    {
        if ($payment->created_at?->lt(now()->subDay()) !== true) {
            return;
        }

        Payment::query()
            ->whereKey($payment->getKey())
            ->whereIn('status', [PaymentStatus::PENDING, PaymentStatus::PROCESSING])
            ->update([
                'status' => PaymentStatus::REQUIRES_REVIEW,
                'failure_code' => 'renewal_outcome_unknown',
                'failure_message' => 'The renewal charge outcome requires manual review.',
            ]);
    }

    private function pendingPayment(Subscription $subscription): ?Payment
    {
        return Payment::query()
            ->where('payable_type', $subscription->getMorphClass())
            ->where('payable_id', $subscription->getKey())
            ->where('purpose', PaymentPurpose::SUBSCRIPTION)
            ->whereIn('status', [
                PaymentStatus::PENDING,
                PaymentStatus::PROCESSING,
                PaymentStatus::REQUIRES_REVIEW,
            ])
            ->latest('id')
            ->first();
    }

    private function isDue(Subscription $subscription): bool
    {
        if (! $subscription->auto_renew
            || $subscription->status !== SubscriptionStatus::Active
            || $subscription->gateway === null
            || $subscription->subscribable_type !== (new Workspace)->getMorphClass()) {
            return false;
        }

        if ($subscription->renewal_retry_at !== null) {
            return $subscription->renewal_retry_at->isPast();
        }

        return $subscription->next_charge_at?->isPast() === true;
    }
}
