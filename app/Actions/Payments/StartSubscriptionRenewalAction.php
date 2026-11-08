<?php

namespace App\Actions\Payments;

use App\DTOs\Payments\CheckoutSession;
use App\DTOs\Payments\InitializePaymentData;
use App\DTOs\Payments\StartSubscriptionRenewalData;
use App\DTOs\Payments\SubscriptionCheckoutResult;
use App\Enums\AccountTypes;
use App\Enums\Payments\PaymentCheckoutProfile;
use App\Enums\Payments\PaymentGatewayName;
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
use App\Services\Payments\IndividualPlanIntervalPricingService;
use App\Services\Payments\PlanPricingService;
use App\Support\Payments\PaymentReferenceGenerator;
use App\ValueObjects\Money;
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
        private IndividualPlanIntervalPricingService $intervalPricing,
    ) {}

    public function execute(StartSubscriptionRenewalData $data): SubscriptionCheckoutResult
    {
        if ($data->paymentSource === SubscriptionPaymentSource::WALLET) {
            $result = $this->walletRenewal->execute($data->subscription->getKey(), true);

            return match ($result) {
                WalletRenewalResult::PAID => new SubscriptionCheckoutResult(
                    Subscription::query()->whereKey($data->subscription->getKey())->firstOrFail(),
                    null,
                ),
                WalletRenewalResult::INSUFFICIENT => throw new CheckoutUnavailable(
                    "Your wallet balance is insufficient. Choose {$this->payments->gatewayName($data->workspace->type)->label()} to continue.",
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

            $renewalCycle = $this->intervalPricing->renewalCycle($subscription);
            $renewalPlan = $subscription->scheduledPlan ?? $subscription->plan;
            $quote = $this->pricing->renewalForPlan($subscription, $renewalPlan, $renewalCycle);
            $gateway = $this->activeExternalGateway($data->paymentSource, $data->workspace->type);
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
                    'plan_id' => $renewalPlan->getKey(),
                    'previous_plan_id' => $subscription->plan_id,
                    'capacity_count' => $quote->capacityCount,
                    'additional_capacity' => $quote->additionalCapacity,
                    'renewal_attempt' => $subscription->renewal_attempts + 1,
                    'renewal_due_at' => $subscription->next_charge_at?->toISOString(),
                    'recurring_consent_at' => now()->toISOString(),
                    'payment_source' => $gateway->value,
                    'billing_cycle' => $renewalCycle?->value,
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
                providerReference: $payment->provider_reference ?? $payment->reference,
                authorizationUrl: $authorizationUrl,
                accessCode: '',
            ));
        }

        try {
            $session = $this->payments->initialize(new InitializePaymentData(
                amount: new Money($payment->amount_minor, $payment->currency),
                email: $data->user->email,
                reference: $payment->reference,
                description: 'Subscription renewal',
                callbackUrl: $data->callbackUrl,
                metadata: $payment->metadata,
                profile: PaymentCheckoutProfile::RECURRING_CARD,
            ), $payment->gateway);
        } catch (PaymentException $exception) {
            $this->failPayment->execute($payment, 'initialization_failed', $exception->getMessage());

            throw $exception;
        }

        $payment->update([
            'provider_reference' => $session->providerReference,
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

    private function activeExternalGateway(
        SubscriptionPaymentSource $source,
        AccountTypes $accountType,
    ): PaymentGatewayName {
        $gateway = $source->gateway();
        $active = $this->payments->gatewayName($accountType);

        if ($gateway === null || $gateway !== $active) {
            throw new CheckoutUnavailable("{$active->label()} is the active external payment provider. Refresh and try again.");
        }

        return $active;
    }
}
