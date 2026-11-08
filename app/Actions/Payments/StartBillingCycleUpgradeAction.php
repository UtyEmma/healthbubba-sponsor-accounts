<?php

namespace App\Actions\Payments;

use App\DTOs\Payments\BillingCycleUpgradeQuote;
use App\DTOs\Payments\BillingCycleUpgradeResult;
use App\DTOs\Payments\CheckoutSession;
use App\DTOs\Payments\InitializePaymentData;
use App\DTOs\Payments\StartBillingCycleUpgradeData;
use App\Enums\CapacityPurchases\CapacityPurchaseStatus;
use App\Enums\Payments\PaymentCheckoutProfile;
use App\Enums\Payments\PaymentGatewayName;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentStatus;
use App\Enums\Payments\SubscriptionPaymentSource;
use App\Enums\Subscriptions\SubscriptionFundingSource;
use App\Enums\Transactions\TransactionFlow;
use App\Enums\Transactions\TransactionStatus;
use App\Enums\Transactions\TransactionTypes;
use App\Exceptions\Payments\CheckoutUnavailable;
use App\Exceptions\Payments\PaymentException;
use App\Models\CapacityPurchase;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Payments\PaymentService;
use App\Services\Payments\BillingCycleUpgradePricingService;
use App\Support\Payments\PaymentReferenceGenerator;
use App\ValueObjects\Money;
use Illuminate\Support\Facades\DB;
use Revoltify\Subscriptionify\Enums\SubscriptionStatus;

final readonly class StartBillingCycleUpgradeAction
{
    public function __construct(
        private BillingCycleUpgradePricingService $pricing,
        private PaymentService $payments,
        private PaymentReferenceGenerator $references,
        private FailPaymentAction $failPayment,
    ) {}

    public function execute(StartBillingCycleUpgradeData $data): BillingCycleUpgradeResult
    {
        [$quote, $payment, $initialize] = DB::transaction(function () use ($data): array {
            $subscription = Subscription::query()
                ->with(['plan.features', 'paymentMethod'])
                ->whereKey($data->subscription->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($subscription->subscribable_type !== $data->workspace->getMorphClass()
                || (int) $subscription->subscribable_id !== (int) $data->workspace->getKey()
                || $subscription->status !== SubscriptionStatus::Active) {
                throw new CheckoutUnavailable('Only an active Individual subscription can upgrade its billing cycle.');
            }

            $this->ensureNoConflictingOperations($subscription);
            $quote = $this->pricing->quote($subscription, $data->targetCycle);

            if ($data->paymentSource === SubscriptionPaymentSource::WALLET) {
                $this->payFromWallet($data, $subscription, $quote);

                return [$quote, null, false];
            }

            $existing = $this->unresolvedPayment($subscription);

            if ($existing instanceof Payment) {
                $this->ensurePaymentMatches($existing, $quote);

                return [$quote, $existing, false];
            }

            $gateway = $this->activeExternalGateway($data);
            $payment = Payment::query()->create([
                'workspace_id' => $data->workspace->getKey(),
                'user_id' => $data->user->getKey(),
                'payable_type' => $subscription->getMorphClass(),
                'payable_id' => $subscription->getKey(),
                'purpose' => PaymentPurpose::BILLING_CYCLE_UPGRADE,
                'status' => PaymentStatus::PENDING,
                'gateway' => $gateway,
                'reference' => $this->references->generate(PaymentPurpose::BILLING_CYCLE_UPGRADE),
                'amount_minor' => $quote->amountDueNow->amountInMinorUnits,
                'currency' => $quote->amountDueNow->currency,
                'metadata' => $this->metadata($data, $subscription, $quote, $gateway),
            ]);
            $payment->update(['metadata' => [...$payment->metadata, 'payment_id' => $payment->getKey()]]);

            return [$quote, $payment->refresh(), true];
        }, 3);

        if (! $payment instanceof Payment) {
            return new BillingCycleUpgradeResult($quote, null);
        }

        if (! $initialize) {
            return new BillingCycleUpgradeResult($quote, $this->checkoutSession($payment));
        }

        try {
            $session = $this->payments->initialize(new InitializePaymentData(
                amount: $quote->amountDueNow,
                email: $data->user->email,
                reference: $payment->reference,
                description: "Upgrade {$data->subscription->plan->name} to {$quote->targetCycle->label()} billing",
                callbackUrl: $data->callbackUrl,
                metadata: $payment->metadata,
                profile: PaymentCheckoutProfile::CARD,
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

        return new BillingCycleUpgradeResult($quote, $session);
    }

    private function ensureNoConflictingOperations(Subscription $subscription): void
    {
        $capacityPending = CapacityPurchase::query()
            ->whereBelongsTo($subscription)
            ->whereIn('status', [CapacityPurchaseStatus::PENDING, CapacityPurchaseStatus::REQUIRES_REVIEW])
            ->exists();
        $otherPaymentPending = Payment::query()
            ->where('payable_type', $subscription->getMorphClass())
            ->where('payable_id', $subscription->getKey())
            ->whereIn('purpose', [PaymentPurpose::SUBSCRIPTION, PaymentPurpose::PLAN_UPGRADE])
            ->whereIn('status', [PaymentStatus::PENDING, PaymentStatus::PROCESSING, PaymentStatus::REQUIRES_REVIEW])
            ->exists();

        if ($capacityPending || $otherPaymentPending) {
            throw new CheckoutUnavailable('Complete the pending subscription operation before upgrading the billing cycle.');
        }
    }

    private function payFromWallet(
        StartBillingCycleUpgradeData $data,
        Subscription $subscription,
        BillingCycleUpgradeQuote $quote,
    ): void {
        if ($this->unresolvedPayment($subscription) instanceof Payment) {
            throw new CheckoutUnavailable('A billing-cycle upgrade payment is already in progress.');
        }

        $wallet = $data->workspace->wallet()->firstOrCreate([], [
            'balance' => '0.00',
            'currency' => $quote->amountDueNow->currency,
        ]);
        $wallet = Wallet::query()->whereKey($wallet->getKey())->lockForUpdate()->firstOrFail();
        $balance = Money::fromMajor($wallet->balance, $wallet->currency);

        if ($wallet->currency !== $quote->amountDueNow->currency) {
            throw new CheckoutUnavailable('The wallet currency does not match this payment.');
        }

        if ($balance->amountInMinorUnits < $quote->amountDueNow->amountInMinorUnits) {
            throw new CheckoutUnavailable(
                "Your wallet balance is insufficient. Choose {$this->payments->gatewayName($data->workspace->type)->label()} to continue.",
            );
        }

        $wallet->update(['balance' => (new Money(
            $balance->amountInMinorUnits - $quote->amountDueNow->amountInMinorUnits,
            $balance->currency,
        ))->toMajorAmount()]);
        $this->applyUpgrade($subscription, $quote, SubscriptionFundingSource::Wallet);

        Transaction::query()->create([
            'owner_type' => $data->workspace->getMorphClass(),
            'owner_id' => $data->workspace->getKey(),
            'recorded_by_user_id' => $data->user->getKey(),
            'transactable_type' => $subscription->getMorphClass(),
            'transactable_id' => $subscription->getKey(),
            'amount' => $quote->amountDueNow->toMajorAmount(),
            'currency' => $quote->amountDueNow->currency,
            'reference' => $this->references->generate(PaymentPurpose::BILLING_CYCLE_UPGRADE),
            'type' => TransactionTypes::PLAN_CHANGE,
            'status' => TransactionStatus::COMPLETED,
            'flow' => TransactionFlow::DEBIT,
            'meta' => [
                'description' => 'Billing cycle upgrade',
                'payment_source' => SubscriptionPaymentSource::WALLET->value,
                'from_billing_cycle' => $quote->currentCycle->value,
                'to_billing_cycle' => $quote->targetCycle->value,
            ],
        ]);
    }

    private function applyUpgrade(
        Subscription $subscription,
        BillingCycleUpgradeQuote $quote,
        SubscriptionFundingSource $source,
    ): void {
        $subscription->update([
            'billing_cycle' => $quote->targetCycle->value,
            'scheduled_billing_cycle' => null,
            'ends_at' => $quote->targetEndsAt,
            'next_charge_at' => $subscription->auto_renew ? $quote->targetEndsAt : null,
            'funding_source' => $source,
            'assigned_by_user_id' => null,
            'complimentary_renews' => false,
        ]);
    }

    private function unresolvedPayment(Subscription $subscription): ?Payment
    {
        return Payment::query()
            ->where('payable_type', $subscription->getMorphClass())
            ->where('payable_id', $subscription->getKey())
            ->where('purpose', PaymentPurpose::BILLING_CYCLE_UPGRADE)
            ->whereIn('status', [PaymentStatus::PENDING, PaymentStatus::PROCESSING, PaymentStatus::REQUIRES_REVIEW])
            ->latest('id')
            ->first();
    }

    private function ensurePaymentMatches(Payment $payment, BillingCycleUpgradeQuote $quote): void
    {
        if ($payment->status === PaymentStatus::REQUIRES_REVIEW) {
            throw new CheckoutUnavailable('The previous billing-cycle upgrade requires payment support review.');
        }

        if ($payment->amount_minor !== $quote->amountDueNow->amountInMinorUnits
            || $payment->currency !== $quote->amountDueNow->currency
            || ($payment->metadata['from_billing_cycle'] ?? null) !== $quote->currentCycle->value
            || ($payment->metadata['to_billing_cycle'] ?? null) !== $quote->targetCycle->value
            || ($payment->metadata['term_ends_at'] ?? null) !== $quote->currentEndsAt->toISOString()) {
            throw new CheckoutUnavailable('A different billing-cycle upgrade is already in progress.');
        }
    }

    /** @return array<string, mixed> */
    private function metadata(
        StartBillingCycleUpgradeData $data,
        Subscription $subscription,
        BillingCycleUpgradeQuote $quote,
        PaymentGatewayName $gateway,
    ): array {
        return [
            'email' => $data->user->email,
            'workspace_id' => $data->workspace->getKey(),
            'purpose' => PaymentPurpose::BILLING_CYCLE_UPGRADE->value,
            'subscription_id' => $subscription->getKey(),
            'plan_id' => $subscription->plan_id,
            'from_billing_cycle' => $quote->currentCycle->value,
            'to_billing_cycle' => $quote->targetCycle->value,
            'current_amount_minor' => $quote->currentAmount->amountInMinorUnits,
            'target_amount_minor' => $quote->targetAmount->amountInMinorUnits,
            'quoted_at' => $quote->quotedAt->toISOString(),
            'term_ends_at' => $quote->currentEndsAt->toISOString(),
            'target_ends_at' => $quote->targetEndsAt->toISOString(),
            'payment_source' => $gateway->value,
        ];
    }

    private function activeExternalGateway(StartBillingCycleUpgradeData $data): PaymentGatewayName
    {
        $gateway = $data->paymentSource->gateway();
        $active = $this->payments->gatewayName($data->workspace->type);

        if ($gateway === null || $gateway !== $active) {
            throw new CheckoutUnavailable("{$active->label()} is the active external payment provider. Refresh and try again.");
        }

        return $active;
    }

    private function checkoutSession(Payment $payment): CheckoutSession
    {
        $url = $payment->provider_metadata['authorization_url'] ?? null;

        if (! is_string($url) || filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new CheckoutUnavailable('The billing-cycle checkout is already being initialized.');
        }

        return new CheckoutSession(
            gateway: $payment->gateway,
            reference: $payment->reference,
            providerReference: $payment->provider_reference ?? $payment->reference,
            authorizationUrl: $url,
            accessCode: '',
        );
    }
}
