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
use App\Enums\Payments\SubscriptionPaymentSource;
use App\Enums\Subscriptions\PlanChangeDirection;
use App\Enums\Transactions\TransactionFlow;
use App\Enums\Transactions\TransactionStatus;
use App\Enums\Transactions\TransactionTypes;
use App\Exceptions\Payments\CheckoutUnavailable;
use App\Exceptions\Payments\PaymentException;
use App\Models\CapacityPurchase;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Payments\PaymentService;
use App\Services\Activity\WorkspaceActivityLogger;
use App\Services\Payments\PlanChangePricingService;
use App\Services\Payments\PlanChangeEligibilityService;
use App\Support\Payments\PaymentReferenceGenerator;
use Illuminate\Support\Facades\DB;
use App\ValueObjects\Money;
use Revoltify\Subscriptionify\Enums\SubscriptionStatus;

final readonly class StartPlanChangeAction
{
    public function __construct(
        private PlanChangePricingService $pricing,
        private PaymentService $payments,
        private PaymentReferenceGenerator $references,
        private FailPaymentAction $failPayment,
        private WorkspaceActivityLogger $activities,
        private PlanChangeEligibilityService $eligibility,
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
                $direction = $this->pricing->direction($subscription, $targetPlan);
                $quoteSubscription = $subscription;

                if ($direction === PlanChangeDirection::DOWNGRADE) {
                    $eligibility = $this->eligibility->assess($data->workspace, $subscription, $targetPlan);

                    if (! $eligibility->available()) {
                        throw new CheckoutUnavailable(implode(' ', $eligibility->violations));
                    }

                    $quoteSubscription = clone $subscription;
                    $quoteSubscription->setAttribute('capacity_count', $eligibility->targetCapacityCount);
                    $quote = $this->pricing->quote($quoteSubscription, $targetPlan);
                    $this->applyDowngrade($data, $subscription, $targetPlan, $quote, $eligibility->targetCapacityCount);

                    return [$quote, null, false];
                }

                $quote = $this->pricing->quote($quoteSubscription, $targetPlan);

                if ($data->paymentSource === SubscriptionPaymentSource::WALLET) {
                    $this->payUpgradeFromWallet($data, $subscription, $targetPlan, $quote);

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
                        'current_base_minor' => $quote->currentBasePrice->amountInMinorUnits,
                        'target_base_minor' => $quote->targetBasePrice->amountInMinorUnits,
                        'quoted_at' => $quote->quotedAt->toISOString(),
                        'term_ends_at' => $subscription->ends_at?->toISOString(),
                        'payment_source' => SubscriptionPaymentSource::PAYSTACK->value,
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

    private function applyDowngrade(
        StartPlanChangeData $data,
        Subscription $subscription,
        Plan $targetPlan,
        PlanChangeQuote $quote,
        int $targetCapacityCount,
    ): void {
        if ($this->unresolvedUpgradePayment($subscription) instanceof Payment) {
            throw new CheckoutUnavailable('A plan upgrade payment is already in progress.');
        }

        $subscription->update([
            'plan_id' => $targetPlan->getKey(),
            'capacity_count' => $targetCapacityCount,
            'scheduled_plan_id' => null,
            'scheduled_plan_change_at' => null,
        ]);

        $this->activities->record($data->workspace, new WorkspaceActivityData(
            type: WorkspaceActivityType::PlanDowngradeApplied,
            title: "Downgraded to {$targetPlan->name}",
            actor: WorkspaceActivityActor::user($data->user),
            subjectType: 'subscription',
            subjectId: $subscription->getKey(),
            subjectName: $targetPlan->name,
            description: 'The plan changed immediately without an additional charge or refund.',
            context: [
                'plan_name' => $targetPlan->name,
                'effective_at' => $quote->effectiveAt->toISOString(),
                'capacity_count' => $targetCapacityCount,
            ],
        ));
    }

    private function payUpgradeFromWallet(
        StartPlanChangeData $data,
        Subscription $subscription,
        Plan $targetPlan,
        PlanChangeQuote $quote,
    ): void {
        if ($this->unresolvedUpgradePayment($subscription) instanceof Payment) {
            throw new CheckoutUnavailable('A plan upgrade payment is already in progress.');
        }

        $wallet = $data->workspace->wallet()->firstOrCreate([], [
            'balance' => '0.00',
            'currency' => $quote->amountDueNow->currency,
        ]);
        $wallet = Wallet::query()->whereKey($wallet->getKey())->lockForUpdate()->firstOrFail();

        if ($wallet->currency !== $quote->amountDueNow->currency) {
            throw new CheckoutUnavailable('The wallet currency does not match this payment.');
        }

        $balance = Money::fromMajor($wallet->balance, $wallet->currency);

        if ($balance->amountInMinorUnits < $quote->amountDueNow->amountInMinorUnits) {
            throw new CheckoutUnavailable('Your wallet balance is insufficient. Choose Paystack to continue.');
        }

        $fromPlanId = (int) $subscription->plan_id;

        $wallet->update([
            'balance' => (new Money(
                $balance->amountInMinorUnits - $quote->amountDueNow->amountInMinorUnits,
                $balance->currency,
            ))->toMajorAmount(),
        ]);
        $subscription->update([
            'plan_id' => $targetPlan->getKey(),
            'capacity_count' => $quote->targetCapacityCount,
            'scheduled_plan_id' => null,
            'scheduled_plan_change_at' => null,
        ]);

        Transaction::query()->create([
            'payment_id' => null,
            'owner_type' => $data->workspace->getMorphClass(),
            'owner_id' => $data->workspace->getKey(),
            'transactable_type' => $subscription->getMorphClass(),
            'transactable_id' => $subscription->getKey(),
            'amount' => $quote->amountDueNow->toMajorAmount(),
            'currency' => $quote->amountDueNow->currency,
            'reference' => $this->references->generate(PaymentPurpose::PLAN_UPGRADE),
            'type' => TransactionTypes::PLAN_CHANGE,
            'status' => TransactionStatus::COMPLETED,
            'flow' => TransactionFlow::DEBIT,
            'meta' => [
                'description' => 'Prorated plan upgrade',
                'payment_source' => SubscriptionPaymentSource::WALLET->value,
                'from_plan_id' => $fromPlanId,
                'to_plan_id' => $targetPlan->getKey(),
            ],
        ]);

        $this->activities->record($data->workspace, new WorkspaceActivityData(
            type: WorkspaceActivityType::PlanUpgradeCompleted,
            title: "Upgraded to {$targetPlan->name}",
            actor: WorkspaceActivityActor::user($data->user),
            subjectType: 'subscription',
            subjectId: $subscription->getKey(),
            subjectName: $targetPlan->name,
            description: 'The prorated upgrade was paid from the workspace wallet.',
            context: [
                'plan_name' => $targetPlan->name,
                'from_plan_id' => $fromPlanId,
                'amount_minor' => $quote->amountDueNow->amountInMinorUnits,
                'currency' => $quote->amountDueNow->currency,
            ],
        ));
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
            || (int) ($payment->metadata['current_base_minor'] ?? 0)
                !== $quote->currentBasePrice->amountInMinorUnits
            || (int) ($payment->metadata['target_base_minor'] ?? 0)
                !== $quote->targetBasePrice->amountInMinorUnits
            || (string) ($payment->metadata['term_ends_at'] ?? '') !== $quote->termEndsAt->toISOString()) {
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
