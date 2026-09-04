<?php

namespace App\Actions\Payments;

use App\DTOs\Payments\CheckoutSession;
use App\DTOs\Payments\InitializePaymentData;
use App\DTOs\Payments\StartSubscriptionRenewalData;
use App\DTOs\Payments\SubscriptionCheckoutResult;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentStatus;
use App\Enums\Payments\SubscriptionPaymentSource;
use App\Enums\Payments\WalletRenewalResult;
use App\Exceptions\Payments\CheckoutUnavailable;
use App\Exceptions\Payments\PaymentException;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Workspace;
use App\Payments\PaymentService;
use App\Services\Payments\PlanPricingService;
use App\Support\Payments\PaymentReferenceGenerator;
use Illuminate\Support\Facades\DB;
use Revoltify\Subscriptionify\Enums\SubscriptionStatus;

final readonly class StartSubscriptionRenewalAction
{
    public function __construct(
        private AttemptWalletSubscriptionRenewalAction $walletRenewal,
        private PlanPricingService $pricing,
        private PaymentService $payments,
        private PaymentReferenceGenerator $references,
        private FailPaymentAction $failPayment,
    ) {}

    public function execute(StartSubscriptionRenewalData $data): SubscriptionCheckoutResult
    {
        if ($data->paymentSource === SubscriptionPaymentSource::WALLET) {
            $result = $this->walletRenewal->execute($data->subscription->getKey(), true);

            return match ($result) {
                WalletRenewalResult::PAID => new SubscriptionCheckoutResult(
                    Subscription::query()->findOrFail($data->subscription->getKey()),
                    null,
                ),
                WalletRenewalResult::INSUFFICIENT => throw new CheckoutUnavailable(
                    'Your wallet balance is insufficient. Choose Paystack to continue.',
                ),
                WalletRenewalResult::NOT_DUE => throw new CheckoutUnavailable(
                    'This subscription is not due for renewal.',
                ),
            };
        }

        $payment = DB::transaction(function () use ($data): Payment {
            $subscription = Subscription::query()
                ->with('plan.features')
                ->whereKey($data->subscription->getKey())
                ->where('subscribable_type', $data->workspace->getMorphClass())
                ->where('subscribable_id', $data->workspace->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $this->isDue($subscription)) {
                throw new CheckoutUnavailable('This subscription is not due for renewal.');
            }

            $existing = Payment::query()
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

            if ($existing instanceof Payment) {
                if ($existing->status === PaymentStatus::REQUIRES_REVIEW) {
                    throw new CheckoutUnavailable('The previous renewal requires payment support review.');
                }

                return $existing;
            }

            $quote = $this->pricing->renewal($subscription);
            $gateway = $this->payments->gatewayName();
            $payment = Payment::query()->create([
                'workspace_id' => $data->workspace->getKey(),
                'user_id' => $data->user->getKey(),
                'payable_type' => $subscription->getMorphClass(),
                'payable_id' => $subscription->getKey(),
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
                    'subscription_id' => $subscription->getKey(),
                    'plan_id' => $subscription->plan_id,
                    'previous_plan_id' => $subscription->plan_id,
                    'capacity_count' => $quote->capacityCount,
                    'additional_capacity' => $quote->additionalCapacity,
                    'renewal_attempt' => $subscription->renewal_attempts + 1,
                    'renewal_due_at' => $subscription->next_charge_at?->toISOString(),
                    'recurring_consent_at' => now()->toISOString(),
                    'payment_source' => SubscriptionPaymentSource::PAYSTACK->value,
                ],
            ]);
            $payment->update(['metadata' => [...$payment->metadata, 'payment_id' => $payment->getKey()]]);

            return $payment->refresh();
        }, 3);

        $authorizationUrl = $payment->provider_metadata['authorization_url'] ?? null;

        if (is_string($authorizationUrl) && filter_var($authorizationUrl, FILTER_VALIDATE_URL) !== false) {
            return new SubscriptionCheckoutResult(null, new CheckoutSession(
                gateway: $payment->gateway,
                reference: $payment->reference,
                authorizationUrl: $authorizationUrl,
                accessCode: '',
            ));
        }

        try {
            $session = $this->payments->initialize(new InitializePaymentData(
                amount: new \App\ValueObjects\Money($payment->amount_minor, $payment->currency),
                email: $data->user->email,
                reference: $payment->reference,
                callbackUrl: $data->callbackUrl,
                metadata: $payment->metadata,
                channels: ['card'],
            ), $payment->gateway);
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

    private function isDue(Subscription $subscription): bool
    {
        if ($subscription->subscribable_type !== (new Workspace)->getMorphClass()) {
            return false;
        }

        if ($subscription->status === SubscriptionStatus::PastDue) {
            return true;
        }

        if ($subscription->status !== SubscriptionStatus::Active) {
            return false;
        }

        return $subscription->renewal_retry_at?->isPast() === true
            || $subscription->next_charge_at?->isPast() === true;
    }
}
