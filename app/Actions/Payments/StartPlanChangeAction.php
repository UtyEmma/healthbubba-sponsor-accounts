<?php

namespace App\Actions\Payments;

use App\DTOs\Activity\WorkspaceActivityActor;
use App\DTOs\Activity\WorkspaceActivityData;
use App\DTOs\Payments\CheckoutSession;
use App\DTOs\Payments\InitializePaymentData;
use App\DTOs\Payments\PlanChangeQuote;
use App\DTOs\Payments\PlanChangeResult;
use App\DTOs\Payments\StartPlanChangeData;
use App\Enums\Activity\WorkspaceActivityType;
use App\Enums\CapacityPurchases\CapacityPurchaseStatus;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentStatus;
use App\Enums\Subscriptions\PlanChangeDirection;
use App\Exceptions\Payments\CheckoutUnavailable;
use App\Exceptions\Payments\PaymentException;
use App\Models\CapacityPurchase;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Payments\PaymentService;
use App\Services\Activity\WorkspaceActivityLogger;
use App\Services\Payments\PlanChangePricingService;
use App\Support\Payments\PaymentReferenceGenerator;
use Illuminate\Support\Facades\DB;
use Revoltify\Subscriptionify\Enums\SubscriptionStatus;

final readonly class StartPlanChangeAction
{
    public function __construct(
        private PlanChangePricingService $pricing,
        private PaymentService $payments,
        private PaymentReferenceGenerator $references,
        private FailPaymentAction $failPayment,
        private WorkspaceActivityLogger $activities,
    ) {}

    public function execute(StartPlanChangeData $data): PlanChangeResult
    {
        [$quote, $payment, $shouldInitialize] = DB::transaction(
            function () use ($data): array {
                $subscription = $this->lockedSubscription($data);
                $targetPlan = Plan::query()
                    ->with('features')
                    ->whereKey($data->targetPlan->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->ensurePlanCanReplaceSubscription($data, $subscription, $targetPlan);
                $this->ensureNoConflictingOperations($subscription);
                $quote = $this->pricing->quote($subscription, $targetPlan);

                if ($quote->direction === PlanChangeDirection::DOWNGRADE) {
                    if ($this->scheduleDowngrade($subscription, $targetPlan, $quote)) {
                        $this->activities->record($data->workspace, new WorkspaceActivityData(
                            type: WorkspaceActivityType::PlanDowngradeScheduled,
                            title: "Scheduled downgrade to {$targetPlan->name}",
                            actor: WorkspaceActivityActor::user($data->user),
                            subjectType: 'subscription',
                            subjectId: $subscription->getKey(),
                            subjectName: $targetPlan->name,
                            description: 'The plan change will take effect at the next billing cycle.',
                            context: [
                                'plan_name' => $targetPlan->name,
                                'effective_at' => $quote->effectiveAt->toISOString(),
                            ],
                        ));
                    }

                    return [$quote, null, false];
                }

                $existingPayment = $this->unresolvedUpgradePayment($subscription);

                if ($existingPayment instanceof Payment) {
                    $this->ensurePaymentMatchesQuote($existingPayment, $targetPlan, $quote);

                    return [$quote, $existingPayment, false];
                }

                $gateway = $this->payments->gatewayName();
                $payment = Payment::query()->create([
                    'workspace_id' => $data->workspace->getKey(),
                    'user_id' => $data->user->getKey(),
                    'payable_type' => $subscription->getMorphClass(),
                    'payable_id' => $subscription->getKey(),
                    'purpose' => PaymentPurpose::PLAN_UPGRADE,
                    'status' => PaymentStatus::PENDING,
                    'gateway' => $gateway,
                    'reference' => $this->references->generate(PaymentPurpose::PLAN_UPGRADE),
                    'amount_minor' => $quote->amountDueNow->amountInMinorUnits,
                    'currency' => $quote->amountDueNow->currency,
                    'metadata' => [
                        'email' => $data->user->email,
                        'workspace_id' => $data->workspace->getKey(),
                        'purpose' => PaymentPurpose::PLAN_UPGRADE->value,
                        'subscription_id' => $subscription->getKey(),
                        'from_plan_id' => $subscription->plan_id,
                        'to_plan_id' => $targetPlan->getKey(),
                        'capacity_count' => $quote->targetCapacityCount,
                        'additional_capacity' => $quote->additionalCapacity,
                        'current_renewal_minor' => $quote->currentRenewal->amountInMinorUnits,
                        'target_renewal_minor' => $quote->targetRenewal->amountInMinorUnits,
                        'term_ends_at' => $quote->effectiveAt->toISOString(),
                    ],
                ]);
                $payment->update([
                    'metadata' => [
                        ...$payment->metadata,
                        'payment_id' => $payment->getKey(),
                    ],
                ]);

                return [$quote, $payment->refresh(), true];
            },
            3,
        );

        if (! $payment instanceof Payment) {
            return new PlanChangeResult($quote, null);
        }

        if (! $shouldInitialize) {
            return new PlanChangeResult($quote, $this->checkoutSession($payment));
        }

        try {
            $session = $this->payments->initialize(
                new InitializePaymentData(
                    amount: $quote->amountDueNow,
                    email: $data->user->email,
                    reference: $payment->reference,
                    callbackUrl: $data->callbackUrl,
                    metadata: $payment->metadata,
                    channels: ['card'],
                ),
                $payment->gateway,
            );
        } catch (PaymentException $exception) {
            $this->failPayment->execute($payment, 'initialization_failed', $exception->getMessage());

            throw $exception;
        }

        $payment->update([
            'provider_reference' => $session->reference,
            'provider_metadata' => [
                'gateway' => $session->gateway->value,
                'authorization_url' => $session->authorizationUrl,
            ],
            'initialized_at' => now(),
        ]);

        return new PlanChangeResult($quote, $session);
    }

    private function lockedSubscription(StartPlanChangeData $data): Subscription
    {
        $subscription = Subscription::query()
            ->with(['plan.features', 'scheduledPlan.features', 'paymentMethod'])
            ->whereKey($data->subscription->getKey())
            ->lockForUpdate()
            ->firstOrFail();

        if ($subscription->subscribable_type !== $data->workspace->getMorphClass()
            || (int) $subscription->subscribable_id !== (int) $data->workspace->getKey()
            || $subscription->status !== SubscriptionStatus::Active) {
            throw new CheckoutUnavailable('The subscription is not active for this workspace.');
        }

        return $subscription;
    }

    private function ensurePlanCanReplaceSubscription(
        StartPlanChangeData $data,
        Subscription $subscription,
        Plan $targetPlan,
    ): void {
        if ($targetPlan->account_type !== $data->workspace->type
            || $targetPlan->account_type !== $subscription->plan->account_type
            || ! $targetPlan->is_active
            || $targetPlan->is_free
            || $targetPlan->trial_days > 0) {
            throw new CheckoutUnavailable('The selected plan is not available for this subscription.');
        }
    }

    private function ensureNoConflictingOperations(Subscription $subscription): void
    {
        $hasCapacityPurchase = CapacityPurchase::query()
            ->whereBelongsTo($subscription)
            ->whereIn('status', [
                CapacityPurchaseStatus::PENDING,
                CapacityPurchaseStatus::REQUIRES_REVIEW,
            ])
            ->exists();

        if ($hasCapacityPurchase) {
            throw new CheckoutUnavailable('Complete the pending capacity purchase before changing plans.');
        }

        $hasPendingRenewal = Payment::query()
            ->where('payable_type', $subscription->getMorphClass())
            ->where('payable_id', $subscription->getKey())
            ->where('purpose', PaymentPurpose::SUBSCRIPTION)
            ->whereIn('status', [
                PaymentStatus::PENDING,
                PaymentStatus::PROCESSING,
                PaymentStatus::REQUIRES_REVIEW,
            ])
            ->exists();

        if ($hasPendingRenewal) {
            throw new CheckoutUnavailable('A subscription renewal payment is already in progress.');
        }
    }

    private function scheduleDowngrade(
        Subscription $subscription,
        Plan $targetPlan,
        PlanChangeQuote $quote,
    ): bool {
        if (! $subscription->auto_renew
            || $subscription->payment_method_id === null
            || $subscription->gateway === null) {
            throw new CheckoutUnavailable('Automatic renewal must be active before scheduling a downgrade.');
        }

        if ($this->unresolvedUpgradePayment($subscription) instanceof Payment) {
            throw new CheckoutUnavailable('A plan upgrade payment is already in progress.');
        }

        if ((int) $subscription->scheduled_plan_id === (int) $targetPlan->getKey()
            && $subscription->scheduled_plan_change_at?->equalTo($quote->effectiveAt) === true) {
            return false;
        }

        $subscription->update([
            'scheduled_plan_id' => $targetPlan->getKey(),
            'scheduled_plan_change_at' => $quote->effectiveAt,
        ]);

        return true;
    }

    private function unresolvedUpgradePayment(Subscription $subscription): ?Payment
    {
        return Payment::query()
            ->where('payable_type', $subscription->getMorphClass())
            ->where('payable_id', $subscription->getKey())
            ->where('purpose', PaymentPurpose::PLAN_UPGRADE)
            ->whereIn('status', [
                PaymentStatus::PENDING,
                PaymentStatus::PROCESSING,
                PaymentStatus::REQUIRES_REVIEW,
            ])
            ->latest('id')
            ->first();
    }

    private function ensurePaymentMatchesQuote(
        Payment $payment,
        Plan $targetPlan,
        PlanChangeQuote $quote,
    ): void {
        if ($payment->status === PaymentStatus::REQUIRES_REVIEW) {
            throw new CheckoutUnavailable('The previous plan upgrade requires payment support review.');
        }

        if ((int) ($payment->metadata['to_plan_id'] ?? 0) !== (int) $targetPlan->getKey()
            || $payment->currency !== $quote->amountDueNow->currency
            || (int) ($payment->metadata['current_renewal_minor'] ?? 0)
                !== $quote->currentRenewal->amountInMinorUnits
            || (int) ($payment->metadata['target_renewal_minor'] ?? 0)
                !== $quote->targetRenewal->amountInMinorUnits
            || (int) ($payment->metadata['capacity_count'] ?? 0)
                !== $quote->targetCapacityCount
            || (string) ($payment->metadata['term_ends_at'] ?? '') !== $quote->effectiveAt->toISOString()) {
            throw new CheckoutUnavailable('A different plan upgrade is already in progress.');
        }
    }

    private function checkoutSession(Payment $payment): CheckoutSession
    {
        $authorizationUrl = $payment->provider_metadata['authorization_url'] ?? null;

        if (! is_string($authorizationUrl)
            || filter_var($authorizationUrl, FILTER_VALIDATE_URL) === false) {
            throw new CheckoutUnavailable('The plan upgrade checkout is already being initialized.');
        }

        return new CheckoutSession(
            gateway: $payment->gateway,
            reference: $payment->reference,
            authorizationUrl: $authorizationUrl,
            accessCode: '',
        );
    }
}
