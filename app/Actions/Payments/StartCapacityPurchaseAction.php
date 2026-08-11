<?php

namespace App\Actions\Payments;

use App\DTOs\CapacityPurchases\CapacityPurchaseQuote;
use App\DTOs\CapacityPurchases\CapacityPurchaseResult;
use App\DTOs\CapacityPurchases\StartCapacityPurchaseData;
use App\DTOs\Payments\CheckoutSession;
use App\DTOs\Payments\InitializePaymentData;
use App\Enums\CapacityPurchases\CapacityPaymentSource;
use App\Enums\CapacityPurchases\CapacityPurchaseStatus;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentStatus;
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
use App\Services\Payments\CapacityPricingService;
use App\Support\Payments\PaymentReferenceGenerator;
use App\ValueObjects\Money;
use Illuminate\Support\Facades\DB;
use Revoltify\Subscriptionify\Enums\SubscriptionStatus;

final readonly class StartCapacityPurchaseAction
{
    public function __construct(
        private CapacityPricingService $pricing,
        private CompleteCapacityPurchaseAction $completePurchase,
        private PaymentService $payments,
        private PaymentReferenceGenerator $references,
        private FailPaymentAction $failPayment,
    ) {}

    public function execute(StartCapacityPurchaseData $data): CapacityPurchaseResult
    {
        return match ($data->paymentSource) {
            CapacityPaymentSource::WALLET => $this->purchaseWithWallet($data),
            CapacityPaymentSource::PAYSTACK => $this->startPaystackCheckout($data),
        };
    }

    private function purchaseWithWallet(StartCapacityPurchaseData $data): CapacityPurchaseResult
    {
        return DB::transaction(function () use ($data): CapacityPurchaseResult {
            $subscription = $this->lockedSubscription($data);
            $this->ensureNoUnresolvedPurchase($subscription);
            $quote = $this->pricing->quote($subscription, $data->quantity);
            $wallet = $this->lockedWallet($data, $quote);
            $balance = Money::fromMajor($wallet->balance, $wallet->currency);

            if ($balance->amountInMinorUnits < $quote->total->amountInMinorUnits) {
                throw new CheckoutUnavailable('Your wallet balance is insufficient for this purchase.');
            }

            $purchase = $this->createPurchase($data, $quote);
            $wallet->update([
                'balance' => (new Money(
                    $balance->amountInMinorUnits - $quote->total->amountInMinorUnits,
                    $balance->currency,
                ))->toMajorAmount(),
            ]);

            $purchase = $this->completePurchase->execute($purchase);

            Transaction::query()->create([
                'payment_id' => null,
                'owner_type' => $data->workspace->getMorphClass(),
                'owner_id' => $data->workspace->getKey(),
                'transactable_type' => $purchase->getMorphClass(),
                'transactable_id' => $purchase->getKey(),
                'amount' => $quote->total->toMajorAmount(),
                'currency' => $quote->total->currency,
                'reference' => $purchase->reference,
                'type' => TransactionTypes::CAPACITY_PURCHASE,
                'status' => TransactionStatus::COMPLETED,
                'flow' => TransactionFlow::DEBIT,
                'meta' => [
                    'description' => 'Additional capacity purchase',
                    'quantity' => $purchase->quantity,
                    'payment_source' => CapacityPaymentSource::WALLET->value,
                ],
            ]);

            return new CapacityPurchaseResult($purchase, null);
        }, 3);
    }

    private function startPaystackCheckout(StartCapacityPurchaseData $data): CapacityPurchaseResult
    {
        [$purchase, $payment, $shouldInitialize] = DB::transaction(
            function () use ($data): array {
                $subscription = $this->lockedSubscription($data);
                $existingPurchase = $this->unresolvedPurchase($subscription);

                if ($existingPurchase instanceof CapacityPurchase) {
                    if ($existingPurchase->status === CapacityPurchaseStatus::REQUIRES_REVIEW) {
                        throw new CheckoutUnavailable('The previous capacity purchase requires payment support review.');
                    }

                    if ($existingPurchase->payment_source !== CapacityPaymentSource::PAYSTACK
                        || $existingPurchase->quantity !== $data->quantity) {
                        throw new CheckoutUnavailable('A different capacity purchase is already in progress.');
                    }

                    $payment = $existingPurchase->payment()->latest('id')->first();

                    if (! $payment instanceof Payment) {
                        throw new CheckoutUnavailable('The existing capacity checkout is still being prepared.');
                    }

                    return [$existingPurchase, $payment, false];
                }

                $quote = $this->pricing->quote($subscription, $data->quantity);
                $purchase = $this->createPurchase($data, $quote);
                $gateway = $this->payments->gatewayName();
                $payment = Payment::query()->create([
                    'workspace_id' => $data->workspace->getKey(),
                    'user_id' => $data->user->getKey(),
                    'payable_type' => $purchase->getMorphClass(),
                    'payable_id' => $purchase->getKey(),
                    'purpose' => PaymentPurpose::CAPACITY_PURCHASE,
                    'status' => PaymentStatus::PENDING,
                    'gateway' => $gateway,
                    'reference' => $purchase->reference,
                    'amount_minor' => $quote->total->amountInMinorUnits,
                    'currency' => $quote->total->currency,
                    'metadata' => [
                        'email' => $data->user->email,
                        'workspace_id' => $data->workspace->getKey(),
                        'purpose' => PaymentPurpose::CAPACITY_PURCHASE->value,
                        'capacity_purchase_id' => $purchase->getKey(),
                        'subscription_id' => $subscription->getKey(),
                        'quantity' => $quote->quantity,
                    ],
                ]);
                $payment->update([
                    'metadata' => [
                        ...$payment->metadata,
                        'payment_id' => $payment->getKey(),
                    ],
                ]);

                return [$purchase, $payment->refresh(), true];
            },
            3,
        );

        if (! $shouldInitialize) {
            return new CapacityPurchaseResult($purchase, $this->checkoutSession($payment));
        }

        try {
            $session = $this->payments->initialize(
                new InitializePaymentData(
                    amount: new Money($payment->amount_minor, $payment->currency),
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

        return new CapacityPurchaseResult($purchase, $session);
    }

    private function lockedSubscription(StartCapacityPurchaseData $data): Subscription
    {
        $subscription = Subscription::query()
            ->with('plan.features')
            ->whereKey($data->subscription->getKey())
            ->lockForUpdate()
            ->firstOrFail();

        if ($subscription->subscribable_type !== $data->workspace->getMorphClass()
            || (int) $subscription->subscribable_id !== (int) $data->workspace->getKey()
            || ! in_array($subscription->status, [
                SubscriptionStatus::Active,
                SubscriptionStatus::Trialing,
            ], true)) {
            throw new CheckoutUnavailable('The subscription is not active for this workspace.');
        }

        return $subscription;
    }

    private function lockedWallet(
        StartCapacityPurchaseData $data,
        CapacityPurchaseQuote $quote,
    ): Wallet {
        $wallet = $data->workspace->wallet()->firstOrCreate([], [
            'balance' => '0.00',
            'currency' => $quote->total->currency,
        ]);

        $wallet = Wallet::query()->whereKey($wallet->getKey())->lockForUpdate()->firstOrFail();

        if ($wallet->currency !== $quote->total->currency) {
            throw new CheckoutUnavailable('The wallet currency does not match this purchase.');
        }

        return $wallet;
    }

    private function createPurchase(
        StartCapacityPurchaseData $data,
        CapacityPurchaseQuote $quote,
    ): CapacityPurchase {
        return CapacityPurchase::query()->create([
            'workspace_id' => $data->workspace->getKey(),
            'subscription_id' => $data->subscription->getKey(),
            'user_id' => $data->user->getKey(),
            'reference' => $this->references->generate(PaymentPurpose::CAPACITY_PURCHASE),
            'payment_source' => $data->paymentSource,
            'status' => CapacityPurchaseStatus::PENDING,
            'quantity' => $quote->quantity,
            'previous_capacity' => $quote->previousCapacity,
            'new_capacity' => $quote->newCapacity,
            'unit_amount_minor' => $quote->unitPrice->amountInMinorUnits,
            'prorated_unit_amount_minor' => $quote->proratedUnitPrice->amountInMinorUnits,
            'amount_minor' => $quote->total->amountInMinorUnits,
            'renewal_amount_minor' => $quote->renewalIncrease->amountInMinorUnits,
            'currency' => $quote->total->currency,
            'term_starts_at' => $quote->termStartsAt,
            'term_ends_at' => $quote->termEndsAt,
        ]);
    }

    private function ensureNoUnresolvedPurchase(Subscription $subscription): void
    {
        if ($this->unresolvedPurchase($subscription) instanceof CapacityPurchase) {
            throw new CheckoutUnavailable('A capacity purchase is already in progress.');
        }
    }

    private function unresolvedPurchase(Subscription $subscription): ?CapacityPurchase
    {
        return CapacityPurchase::query()
            ->whereBelongsTo($subscription)
            ->whereIn('status', [
                CapacityPurchaseStatus::PENDING,
                CapacityPurchaseStatus::REQUIRES_REVIEW,
            ])
            ->latest('id')
            ->first();
    }

    private function checkoutSession(Payment $payment): CheckoutSession
    {
        $authorizationUrl = $payment->provider_metadata['authorization_url'] ?? null;

        if (! is_string($authorizationUrl)
            || filter_var($authorizationUrl, FILTER_VALIDATE_URL) === false) {
            throw new CheckoutUnavailable('The capacity checkout is already being initialized.');
        }

        return new CheckoutSession(
            gateway: $payment->gateway,
            reference: $payment->reference,
            authorizationUrl: $authorizationUrl,
            accessCode: '',
        );
    }
}
