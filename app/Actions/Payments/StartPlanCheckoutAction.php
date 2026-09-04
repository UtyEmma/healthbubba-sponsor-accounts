<?php

namespace App\Actions\Payments;

use App\DTOs\Payments\CheckoutSession;
use App\DTOs\Payments\InitializePaymentData;
use App\DTOs\Payments\PlanCharge;
use App\DTOs\Payments\StartPlanCheckoutData;
use App\DTOs\Payments\SubscriptionCheckoutResult;
use App\Enums\AccountTypes;
use App\Enums\Activity\WorkspaceActivityType;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentStatus;
use App\Enums\Payments\SubscriptionPaymentSource;
use App\Enums\Transactions\TransactionFlow;
use App\Enums\Transactions\TransactionStatus;
use App\Enums\Transactions\TransactionTypes;
use App\DTOs\Activity\WorkspaceActivityActor;
use App\DTOs\Activity\WorkspaceActivityData;
use App\Exceptions\Payments\CheckoutUnavailable;
use App\Exceptions\Payments\PaymentException;
use App\Exceptions\Payments\PaymentVerificationFailed;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Payments\PaymentService;
use App\Services\Payments\PlanPricingService;
use App\Services\Activity\WorkspaceActivityLogger;
use App\Support\Payments\PaymentReferenceGenerator;
use Illuminate\Support\Facades\DB;
use App\ValueObjects\Money;
use Revoltify\Subscriptionify\Enums\SubscriptionStatus;

final readonly class StartPlanCheckoutAction
{
    public function __construct(
        private PaymentService $payments,
        private PaymentReferenceGenerator $references,
        private PlanPricingService $pricing,
        private FailPaymentAction $failPayment,
        private CompletePaymentAction $completePayment,
        private WorkspaceActivityLogger $activities,
    ) {}

    public function execute(StartPlanCheckoutData $data): SubscriptionCheckoutResult
    {
        $this->ensurePlanCanBePurchased($data);

        $quote = $this->pricing->checkout($data->plan, $data->additionalCapacity);
        $this->ensureNoActiveSubscription($data);

        if ($data->paymentSource === SubscriptionPaymentSource::WALLET) {
            return $this->payFromWallet($data);
        }

        $gateway = $this->payments->gatewayName($data->gateway);
        $existingSession = $this->resumeUnresolvedCheckout($data, $quote);

        if ($existingSession instanceof CheckoutSession) {
            return new SubscriptionCheckoutResult(null, $existingSession);
        }

        $payment = DB::transaction(function () use ($data, $quote, $gateway): Payment {
            $data->workspace->newQuery()
                ->whereKey($data->workspace->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensureNoActiveOrPendingCheckout($data);

            return Payment::query()->create([
                'workspace_id' => $data->workspace->getKey(),
                'user_id' => $data->user->getKey(),
                'payable_type' => $data->plan->getMorphClass(),
                'payable_id' => $data->plan->getKey(),
                'purpose' => PaymentPurpose::SUBSCRIPTION,
                'status' => PaymentStatus::PENDING,
                'gateway' => $gateway,
                'reference' => $this->references->generate(PaymentPurpose::SUBSCRIPTION),
                'amount_minor' => $quote->money->amountInMinorUnits,
                'currency' => $quote->money->currency,
                'metadata' => [
                    'email' => $data->user->email,
                    'workspace_id' => $data->workspace->getKey(),
                    'purpose' => PaymentPurpose::SUBSCRIPTION->value,
                    'plan_id' => $data->plan->getKey(),
                    'capacity_count' => $quote->capacityCount,
                    'additional_capacity' => $quote->additionalCapacity,
                    'billing_period' => $data->plan->billing_period,
                    'billing_interval' => $data->plan->billing_interval->value,
                    'recurring_consent_at' => now()->toISOString(),
                    'payment_source' => SubscriptionPaymentSource::PAYSTACK->value,
                ],
            ]);
        }, 3);

        $metadata = [
            ...$payment->metadata,
            'payment_id' => $payment->getKey(),
        ];

        $payment->update(['metadata' => $metadata]);

        try {
            $session = $this->payments->initialize(
                new InitializePaymentData(
                    amount: $quote->money,
                    email: $data->user->email,
                    reference: $payment->reference,
                    callbackUrl: $data->callbackUrl,
                    metadata: $metadata,
                    channels: ['card'],
                ),
                $gateway,
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

        return new SubscriptionCheckoutResult(null, $session);
    }

    private function payFromWallet(StartPlanCheckoutData $data): SubscriptionCheckoutResult
    {
        return DB::transaction(function () use ($data): SubscriptionCheckoutResult {
            $data->workspace->newQuery()
                ->whereKey($data->workspace->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $this->ensureNoActiveOrPendingCheckout($data);
            $quote = $this->pricing->checkout($data->plan, $data->additionalCapacity);
            $wallet = $data->workspace->wallet()->firstOrCreate([], [
                'balance' => '0.00',
                'currency' => $quote->money->currency,
            ]);
            $wallet = Wallet::query()->whereKey($wallet->getKey())->lockForUpdate()->firstOrFail();

            if ($wallet->currency !== $quote->money->currency) {
                throw new CheckoutUnavailable('The wallet currency does not match this payment.');
            }

            $balance = Money::fromMajor($wallet->balance, $wallet->currency);

            if ($balance->amountInMinorUnits < $quote->money->amountInMinorUnits) {
                throw new CheckoutUnavailable('Your wallet balance is insufficient. Choose Paystack to continue.');
            }

            $startsAt = now();
            $endsAt = $data->plan->calculateEndsAt($startsAt);
            $subscription = Subscription::query()->create([
                'subscribable_type' => $data->workspace->getMorphClass(),
                'subscribable_id' => $data->workspace->getKey(),
                'plan_id' => $data->plan->getKey(),
                'capacity_count' => $quote->capacityCount,
                'status' => SubscriptionStatus::Active,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'auto_renew' => true,
                'next_charge_at' => $endsAt,
                'renewal_attempts' => 0,
                'renewal_retry_at' => null,
                'recurring_consent_at' => $startsAt,
            ]);

            $wallet->update([
                'balance' => (new Money(
                    $balance->amountInMinorUnits - $quote->money->amountInMinorUnits,
                    $balance->currency,
                ))->toMajorAmount(),
            ]);

            Transaction::query()->create([
                'payment_id' => null,
                'owner_type' => $data->workspace->getMorphClass(),
                'owner_id' => $data->workspace->getKey(),
                'transactable_type' => $subscription->getMorphClass(),
                'transactable_id' => $subscription->getKey(),
                'amount' => $quote->money->toMajorAmount(),
                'currency' => $quote->money->currency,
                'reference' => $this->references->generate(PaymentPurpose::SUBSCRIPTION),
                'type' => TransactionTypes::SUBSCRIPTION,
                'status' => TransactionStatus::COMPLETED,
                'flow' => TransactionFlow::DEBIT,
                'meta' => [
                    'description' => 'Plan subscription payment',
                    'payment_source' => SubscriptionPaymentSource::WALLET->value,
                    'plan_id' => $data->plan->getKey(),
                ],
            ]);

            $this->activities->record($data->workspace, new WorkspaceActivityData(
                type: WorkspaceActivityType::SubscriptionActivated,
                title: "Activated {$data->plan->name}",
                actor: WorkspaceActivityActor::user($data->user),
                subjectType: 'subscription',
                subjectId: $subscription->getKey(),
                subjectName: $data->plan->name,
                description: 'The subscription was paid from the workspace wallet.',
                context: [
                    'plan_name' => $data->plan->name,
                    'amount_minor' => $quote->money->amountInMinorUnits,
                    'currency' => $quote->money->currency,
                ],
            ));

            return new SubscriptionCheckoutResult($subscription->refresh(), null);
        }, 3);
    }

    private function ensurePlanCanBePurchased(StartPlanCheckoutData $data): void
    {
        $plan = $data->plan;

        if ($plan->account_type !== $data->workspace->type) {
            throw new CheckoutUnavailable('The selected plan is not available for this workspace.');
        }

        if (! $plan->is_active || $plan->is_free || $plan->trial_days > 0) {
            throw new CheckoutUnavailable('Only active paid plans without a trial are available for online checkout.');
        }

        if ($plan->account_type === AccountTypes::INSTITUTION) {
            throw new CheckoutUnavailable('Institutional plans are not available for online checkout.');
        }
    }

    private function ensureNoActiveOrPendingCheckout(StartPlanCheckoutData $data): void
    {
        $this->ensureNoActiveSubscription($data);

        $hasUnresolvedCheckout = Payment::query()
            ->where('workspace_id', $data->workspace->getKey())
            ->where('purpose', PaymentPurpose::SUBSCRIPTION)
            ->where('payable_type', $data->plan->getMorphClass())
            ->whereIn('status', [
                PaymentStatus::PENDING,
                PaymentStatus::PROCESSING,
                PaymentStatus::REQUIRES_REVIEW,
            ])
            ->exists();

        if ($hasUnresolvedCheckout) {
            throw new CheckoutUnavailable('A subscription checkout is already in progress.');
        }
    }

    private function ensureNoActiveSubscription(StartPlanCheckoutData $data): void
    {
        $hasActiveSubscription = $data->workspace->subscriptions()
            ->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::Trialing])
            ->exists();

        if ($hasActiveSubscription) {
            throw new CheckoutUnavailable('This workspace already has an active subscription.');
        }
    }

    private function resumeUnresolvedCheckout(
        StartPlanCheckoutData $data,
        PlanCharge $quote,
    ): ?CheckoutSession {
        $payment = Payment::query()
            ->where('workspace_id', $data->workspace->getKey())
            ->where('purpose', PaymentPurpose::SUBSCRIPTION)
            ->where('payable_type', $data->plan->getMorphClass())
            ->whereIn('status', [
                PaymentStatus::PENDING,
                PaymentStatus::PROCESSING,
                PaymentStatus::REQUIRES_REVIEW,
            ])
            ->latest('id')
            ->first();

        if (! $payment instanceof Payment) {
            return null;
        }

        if ($payment->status === PaymentStatus::REQUIRES_REVIEW) {
            throw new CheckoutUnavailable('The previous checkout requires payment support review.');
        }

        if ((int) $payment->payable_id !== (int) $data->plan->getKey()
            || $payment->amount_minor !== $quote->money->amountInMinorUnits
            || $payment->currency !== $quote->money->currency
            || (int) ($payment->metadata['additional_capacity']
                ?? $payment->metadata['additional_seats']
                ?? 0) !== $quote->additionalCapacity) {
            throw new CheckoutUnavailable(
                'A checkout with different plan or seat pricing is already in progress.',
            );
        }

        try {
            $payment = $this->completePayment->execute(
                reference: $payment->reference,
                gateway: $payment->gateway,
            );
        } catch (PaymentVerificationFailed $exception) {
            report($exception);

            throw new CheckoutUnavailable('The previous checkout requires payment support review.');
        } catch (PaymentException $exception) {
            report($exception);

            return $this->resumableCheckoutSession($data, $payment);
        }

        if ($payment->status === PaymentStatus::SUCCEEDED) {
            throw new CheckoutUnavailable('The previous checkout has already activated a subscription.');
        }

        if ($payment->status === PaymentStatus::FAILED) {
            return null;
        }

        return $this->resumableCheckoutSession($data, $payment);
    }

    private function resumableCheckoutSession(
        StartPlanCheckoutData $data,
        Payment $payment,
    ): CheckoutSession {
        $this->ensureNoActiveSubscription($data);

        return $this->checkoutSession($payment);
    }

    private function checkoutSession(Payment $payment): CheckoutSession
    {
        $authorizationUrl = $payment->provider_metadata['authorization_url'] ?? null;

        if (! is_string($authorizationUrl)
            || filter_var($authorizationUrl, FILTER_VALIDATE_URL) === false) {
            throw new CheckoutUnavailable('A subscription checkout is already being initialized.');
        }

        return new CheckoutSession(
            gateway: $payment->gateway,
            reference: $payment->reference,
            authorizationUrl: $authorizationUrl,
            accessCode: '',
        );
    }
}
