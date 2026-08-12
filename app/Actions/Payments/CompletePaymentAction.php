<?php

namespace App\Actions\Payments;

use App\DTOs\Payments\PaymentMethodData;
use App\DTOs\Payments\PaymentVerification;
use App\Enums\CapacityPurchases\CapacityPurchaseStatus;
use App\Enums\Payments\PaymentGatewayName;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentStatus;
use App\Enums\Transactions\TransactionFlow;
use App\Enums\Transactions\TransactionStatus;
use App\Enums\Transactions\TransactionTypes;
use App\Events\Payments\PaymentCompleted;
use App\Exceptions\Payments\CheckoutUnavailable;
use App\Exceptions\Payments\PaymentVerificationFailed;
use App\Models\CapacityPurchase;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\Workspace;
use App\Payments\PaymentService;
use App\Services\Payments\PlanPricingService;
use App\ValueObjects\Money;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Revoltify\Subscriptionify\Enums\Interval;
use Revoltify\Subscriptionify\Enums\SubscriptionStatus;

final readonly class CompletePaymentAction
{
    public function __construct(
        private PaymentService $payments,
        private FailPaymentAction $failPayment,
        private CompleteCapacityPurchaseAction $completeCapacityPurchase,
        private PlanPricingService $planPricing,
    ) {}

    public function execute(
        string $reference,
        ?PaymentVerification $verification = null,
        ?PaymentGatewayName $gateway = null,
    ): Payment {
        $payment = Payment::query()
            ->where('reference', $reference)
            ->when($gateway !== null, fn ($query) => $query->where('gateway', $gateway))
            ->firstOrFail();

        if ($payment->status === PaymentStatus::SUCCEEDED) {
            return $payment;
        }

        $verification ??= $this->payments->verify($payment->reference, $payment->gateway);

        if ($verification->status !== PaymentStatus::SUCCEEDED) {
            if ($verification->status === PaymentStatus::FAILED) {
                $this->failPayment->execute(
                    $payment,
                    'gateway_failed',
                    $verification->gatewayResponse ?? 'The gateway reported that the payment failed.',
                );
            }

            return $payment->refresh();
        }

        try {
            $this->ensureVerificationMatches($payment, $verification);
        } catch (PaymentVerificationFailed $exception) {
            Payment::query()
                ->whereKey($payment->getKey())
                ->where('status', '!=', PaymentStatus::SUCCEEDED)
                ->update([
                    'status' => PaymentStatus::REQUIRES_REVIEW,
                    'provider_reference' => $verification->providerTransactionId ?? $verification->reference,
                    'provider_metadata' => $verification->providerData,
                    'failure_code' => 'verification_mismatch',
                    'failure_message' => $exception->getMessage(),
                    'paid_at' => $verification->paidAt ?? now(),
                    'failed_at' => null,
                ]);

            $this->markCapacityPurchaseRequiresReview($payment, $exception->getMessage());

            throw $exception;
        }

        try {
            return DB::transaction(function () use ($payment, $verification): Payment {
                $lockedPayment = Payment::query()
                    ->whereKey($payment->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedPayment->status === PaymentStatus::SUCCEEDED) {
                    return $lockedPayment;
                }

                $workspace = Workspace::query()
                    ->whereKey($lockedPayment->workspace_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $paymentMethod = $this->shouldStoreReusablePaymentMethod($lockedPayment)
                    ? $this->storeReusablePaymentMethod(
                        workspace: $workspace,
                        gateway: $lockedPayment->gateway,
                        data: $verification->paymentMethod,
                    )
                    : null;

                $transactable = match ($lockedPayment->purpose) {
                    PaymentPurpose::WALLET_TOP_UP => $this->completeWalletFunding(
                        payment: $lockedPayment,
                        workspace: $workspace,
                    ),
                    PaymentPurpose::SUBSCRIPTION => $this->completeSubscriptionPayment(
                        payment: $lockedPayment,
                        workspace: $workspace,
                        paymentMethod: $paymentMethod,
                    ),
                    PaymentPurpose::CAPACITY_PURCHASE => $this->completeCapacityPurchasePayment(
                        payment: $lockedPayment,
                        workspace: $workspace,
                    ),
                    PaymentPurpose::PLAN_UPGRADE => $this->completePlanUpgradePayment(
                        payment: $lockedPayment,
                        workspace: $workspace,
                    ),
                };

                if ($paymentMethod instanceof PaymentMethod) {
                    $paymentMethod->update(['last_used_at' => now()]);
                }

                $this->recordLedgerTransaction($lockedPayment, $workspace, $transactable);

                $lockedPayment->update([
                    'payment_method_id' => $paymentMethod?->getKey() ?? $lockedPayment->payment_method_id,
                    'status' => PaymentStatus::SUCCEEDED,
                    'provider_reference' => $verification->providerTransactionId ?? $verification->reference,
                    'provider_metadata' => $verification->providerData,
                    'failure_code' => null,
                    'failure_message' => null,
                    'paid_at' => $verification->paidAt ?? now(),
                    'failed_at' => null,
                ]);

                $lockedPayment->refresh();
                PaymentCompleted::dispatch($lockedPayment);

                return $lockedPayment;
            }, 3);
        } catch (CheckoutUnavailable|PaymentVerificationFailed|ModelNotFoundException|InvalidArgumentException $exception) {
            $this->quarantineFulfillmentFailure($payment, $verification);

            if ($exception instanceof PaymentVerificationFailed) {
                throw $exception;
            }

            throw new PaymentVerificationFailed(
                'The verified payment could not be fulfilled automatically.',
                previous: $exception,
            );
        }
    }

    private function ensureVerificationMatches(Payment $payment, PaymentVerification $verification): void
    {
        $expected = new Money($payment->amount_minor, $payment->currency);
        $expectedEmail = mb_strtolower(trim((string) ($payment->metadata['email'] ?? '')));

        if (! hash_equals($payment->gateway->value, $verification->gateway->value)) {
            throw new PaymentVerificationFailed('The verified gateway does not match this payment.');
        }

        if (! hash_equals($payment->reference, $verification->reference)) {
            throw new PaymentVerificationFailed('The verified reference does not match this payment.');
        }

        if (! $verification->amount->equals($expected)) {
            throw new PaymentVerificationFailed('The verified amount or currency does not match this payment.');
        }

        if ($expectedEmail === '' || ! hash_equals($expectedEmail, mb_strtolower(trim($verification->customerEmail)))) {
            throw new PaymentVerificationFailed('The verified customer email does not match this payment.');
        }

        $this->ensureMetadataMatches($payment, $verification);
        $this->ensurePurposeMatchesPayable($payment);
    }

    private function ensureMetadataMatches(Payment $payment, PaymentVerification $verification): void
    {
        $expected = [
            'payment_id' => $payment->getKey(),
            'workspace_id' => $payment->workspace_id,
            'purpose' => $payment->purpose->value,
        ];

        foreach ($expected as $key => $value) {
            if (! array_key_exists($key, $verification->metadata)
                || (string) $verification->metadata[$key] !== (string) $value) {
                throw new PaymentVerificationFailed("The verified payment {$key} is invalid.");
            }
        }
    }

    private function ensurePurposeMatchesPayable(Payment $payment): void
    {
        $expectedType = match ($payment->purpose) {
            PaymentPurpose::WALLET_TOP_UP => (new Wallet)->getMorphClass(),
            PaymentPurpose::SUBSCRIPTION => in_array(
                $payment->payable_type,
                [(new Plan)->getMorphClass(), (new Subscription)->getMorphClass()],
                true,
            ) ? $payment->payable_type : null,
            PaymentPurpose::CAPACITY_PURCHASE => (new CapacityPurchase)->getMorphClass(),
            PaymentPurpose::PLAN_UPGRADE => (new Subscription)->getMorphClass(),
        };

        if ($expectedType === null || $payment->payable_type !== $expectedType) {
            throw new PaymentVerificationFailed('The payment purpose does not match its payable record.');
        }
    }

    private function completeWalletFunding(Payment $payment, Workspace $workspace): Wallet
    {
        $wallet = Wallet::query()
            ->whereKey($payment->payable_id)
            ->where('owner_type', $workspace->getMorphClass())
            ->where('owner_id', $workspace->getKey())
            ->lockForUpdate()
            ->firstOrFail();

        if ($wallet->currency !== $payment->currency) {
            throw new PaymentVerificationFailed('The wallet currency does not match this payment.');
        }

        $balance = Money::fromMajor($wallet->balance, $wallet->currency);
        $credit = new Money($payment->amount_minor, $payment->currency);

        $wallet->update([
            'balance' => $balance->add($credit)->toMajorAmount(),
        ]);

        return $wallet->refresh();
    }

    private function completeCapacityPurchasePayment(
        Payment $payment,
        Workspace $workspace,
    ): CapacityPurchase {
        $purchase = CapacityPurchase::query()
            ->whereKey($payment->payable_id)
            ->whereBelongsTo($workspace)
            ->lockForUpdate()
            ->firstOrFail();

        return $this->completeCapacityPurchase->execute($purchase);
    }

    private function completePlanUpgradePayment(
        Payment $payment,
        Workspace $workspace,
    ): Subscription {
        $fromPlanId = $this->positiveMetadataInteger($payment, 'from_plan_id', 'plan upgrade');
        $toPlanId = $this->positiveMetadataInteger($payment, 'to_plan_id', 'plan upgrade');
        $termEndsAt = $payment->metadata['term_ends_at'] ?? null;

        if (! is_string($termEndsAt)) {
            throw new PaymentVerificationFailed('The plan upgrade term is invalid.');
        }

        $subscription = Subscription::query()
            ->with('plan.features')
            ->whereKey($payment->payable_id)
            ->where('subscribable_type', $workspace->getMorphClass())
            ->where('subscribable_id', $workspace->getKey())
            ->lockForUpdate()
            ->firstOrFail();

        if ((int) $subscription->plan_id !== $fromPlanId
            || $subscription->status !== SubscriptionStatus::Active
            || $subscription->ends_at->toISOString() !== $termEndsAt
            || ! $subscription->ends_at->isFuture()) {
            throw new PaymentVerificationFailed('The subscription changed before the upgrade payment completed.');
        }

        $targetPlan = Plan::query()
            ->with('features')
            ->whereKey($toPlanId)
            ->lockForUpdate()
            ->firstOrFail();

        if ($targetPlan->account_type !== $subscription->plan->account_type
            || ! $targetPlan->is_active
            || $targetPlan->is_free
            || $targetPlan->trial_days > 0) {
            throw new PaymentVerificationFailed('The upgrade target plan is no longer available.');
        }

        $targetCharge = $this->planPricing->renewalForPlan($subscription, $targetPlan);
        $currentCharge = $this->planPricing->renewal($subscription);
        $currentRenewalMinor = $this->positiveMetadataInteger(
            $payment,
            'current_renewal_minor',
            'plan upgrade',
        );
        $targetRenewalMinor = $this->positiveMetadataInteger(
            $payment,
            'target_renewal_minor',
            'plan upgrade',
        );
        $capacityCount = $this->positiveMetadataInteger(
            $payment,
            'capacity_count',
            'plan upgrade',
        );

        if ($currentRenewalMinor !== $currentCharge->money->amountInMinorUnits
            || $targetRenewalMinor !== $targetCharge->money->amountInMinorUnits
            || $targetRenewalMinor <= $currentRenewalMinor
            || $capacityCount !== $targetCharge->capacityCount) {
            throw new PaymentVerificationFailed('The plan pricing changed before the upgrade payment completed.');
        }

        $subscription->update([
            'plan_id' => $targetPlan->getKey(),
            'capacity_count' => $capacityCount,
            'scheduled_plan_id' => null,
            'scheduled_plan_change_at' => null,
        ]);

        return $subscription->refresh();
    }

    private function completeSubscriptionPayment(
        Payment $payment,
        Workspace $workspace,
        ?PaymentMethod $paymentMethod,
    ): Subscription {
        if ($payment->payable_type === (new Subscription)->getMorphClass()) {
            return $this->renewSubscription($payment, $workspace, $paymentMethod);
        }

        $plan = Plan::query()
            ->whereKey($payment->payable_id)
            ->lockForUpdate()
            ->firstOrFail();

        $hasActiveSubscription = Subscription::query()
            ->where('subscribable_type', $workspace->getMorphClass())
            ->where('subscribable_id', $workspace->getKey())
            ->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::Trialing])
            ->lockForUpdate()
            ->exists();

        if ($hasActiveSubscription) {
            throw new PaymentVerificationFailed('This workspace already has an active subscription.');
        }

        $startsAt = now();
        $endsAt = $this->initialSubscriptionEndsAt($payment, $startsAt);
        $autoRenew = $paymentMethod?->reusable === true;
        $consentedAt = $payment->metadata['recurring_consent_at'] ?? null;

        $attributes = [
            'subscribable_type' => $workspace->getMorphClass(),
            'subscribable_id' => $workspace->getKey(),
            'plan_id' => $plan->getKey(),
            'status' => SubscriptionStatus::Active,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'payment_method_id' => $paymentMethod?->getKey(),
            'gateway' => $payment->gateway,
            'auto_renew' => $autoRenew,
            'next_charge_at' => $autoRenew ? $endsAt : null,
            'renewal_attempts' => 0,
            'renewal_retry_at' => null,
            'recurring_consent_at' => is_string($consentedAt) ? CarbonImmutable::parse($consentedAt) : null,
        ];

        $capacityCount = $payment->metadata['capacity_count']
            ?? $payment->metadata['seat_count']
            ?? null;

        if (is_int($capacityCount) || (is_string($capacityCount) && ctype_digit($capacityCount))) {
            $attributes['capacity_count'] = (int) $capacityCount;
        }

        return Subscription::query()->create($attributes);
    }

    private function initialSubscriptionEndsAt(
        Payment $payment,
        CarbonInterface $startsAt,
    ): CarbonInterface {
        $period = $payment->metadata['billing_period'] ?? null;
        $interval = $payment->metadata['billing_interval'] ?? null;

        if (is_string($period) && ctype_digit($period)) {
            $period = (int) $period;
        }

        $billingInterval = is_string($interval) ? Interval::tryFrom($interval) : null;

        if (! is_int($period) || $period < 1 || ! $billingInterval instanceof Interval) {
            throw new PaymentVerificationFailed('The paid subscription cadence is invalid.');
        }

        return $billingInterval->addToDate($startsAt, $period);
    }

    private function renewSubscription(
        Payment $payment,
        Workspace $workspace,
        ?PaymentMethod $paymentMethod,
    ): Subscription {
        $subscription = Subscription::query()
            ->with(['plan.features', 'scheduledPlan.features'])
            ->whereKey($payment->payable_id)
            ->where('subscribable_type', $workspace->getMorphClass())
            ->where('subscribable_id', $workspace->getKey())
            ->lockForUpdate()
            ->firstOrFail();

        $renewalPlanId = $this->positiveMetadataInteger($payment, 'plan_id', 'subscription renewal');
        $renewalPlan = $subscription->plan;

        if ($renewalPlanId !== (int) $subscription->plan_id) {
            if (! $subscription->scheduledPlan instanceof Plan
                || (int) $subscription->scheduled_plan_id !== $renewalPlanId
                || $subscription->scheduled_plan_change_at === null
                || $subscription->scheduled_plan_change_at->isAfter(now())) {
                throw new PaymentVerificationFailed('The scheduled plan no longer matches this renewal payment.');
            }

            $renewalPlan = $subscription->scheduledPlan;
        }

        if (! $renewalPlan->is_active || $renewalPlan->is_free || $renewalPlan->trial_days > 0) {
            throw new PaymentVerificationFailed('The renewal plan is no longer available.');
        }

        $renewalCharge = $this->planPricing->renewalForPlan($subscription, $renewalPlan);
        $capacityCount = $this->positiveMetadataInteger(
            $payment,
            'capacity_count',
            'subscription renewal',
        );

        if ($payment->amount_minor !== $renewalCharge->money->amountInMinorUnits
            || $payment->currency !== $renewalCharge->money->currency
            || $capacityCount !== $renewalCharge->capacityCount) {
            throw new PaymentVerificationFailed('The renewal pricing changed before payment completed.');
        }
        $now = now();
        $renewalBase = $subscription->ends_at?->isFuture() === true
            ? $subscription->ends_at
            : $now;
        $endsAt = $renewalPlan->calculateEndsAt($renewalBase);

        $subscription->update([
            'plan_id' => $renewalPlan->getKey(),
            'scheduled_plan_id' => null,
            'scheduled_plan_change_at' => null,
            'capacity_count' => $capacityCount,
            'status' => SubscriptionStatus::Active,
            'starts_at' => $now,
            'ends_at' => $endsAt,
            'renewed_at' => $now,
            'cancelled_at' => null,
            'next_charge_at' => $subscription->auto_renew ? $endsAt : null,
            'renewal_attempts' => 0,
            'renewal_retry_at' => null,
            'payment_method_id' => $paymentMethod?->getKey() ?? $subscription->payment_method_id,
        ]);

        return $subscription->refresh();
    }

    private function shouldStoreReusablePaymentMethod(Payment $payment): bool
    {
        if ($payment->purpose !== PaymentPurpose::SUBSCRIPTION) {
            return false;
        }

        if ($payment->payable_type === (new Subscription)->getMorphClass()) {
            return true;
        }

        $consentedAt = $payment->metadata['recurring_consent_at'] ?? null;

        return $payment->payable_type === (new Plan)->getMorphClass()
            && is_string($consentedAt)
            && trim($consentedAt) !== '';
    }

    private function positiveMetadataInteger(Payment $payment, string $key, string $context): int
    {
        $value = $payment->metadata[$key] ?? null;

        if (is_string($value) && ctype_digit($value)) {
            $value = (int) $value;
        }

        if (! is_int($value) || $value < 1) {
            throw new PaymentVerificationFailed("The {$context} {$key} is invalid.");
        }

        return $value;
    }

    private function quarantineFulfillmentFailure(
        Payment $payment,
        PaymentVerification $verification,
    ): void {
        Payment::query()
            ->whereKey($payment->getKey())
            ->where('status', '!=', PaymentStatus::SUCCEEDED)
            ->update([
                'status' => PaymentStatus::REQUIRES_REVIEW,
                'provider_reference' => $verification->providerTransactionId ?? $verification->reference,
                'provider_metadata' => $verification->providerData,
                'failure_code' => 'fulfillment_failed',
                'failure_message' => 'The verified payment could not be fulfilled automatically.',
                'paid_at' => $verification->paidAt ?? now(),
                'failed_at' => null,
            ]);

        $this->markCapacityPurchaseRequiresReview(
            $payment,
            'The verified capacity purchase could not be fulfilled automatically.',
        );
    }

    private function markCapacityPurchaseRequiresReview(Payment $payment, string $message): void
    {
        if ($payment->purpose !== PaymentPurpose::CAPACITY_PURCHASE
            || $payment->payable_type !== (new CapacityPurchase)->getMorphClass()) {
            return;
        }

        CapacityPurchase::query()
            ->whereKey($payment->payable_id)
            ->where('status', CapacityPurchaseStatus::PENDING)
            ->update([
                'status' => CapacityPurchaseStatus::REQUIRES_REVIEW,
                'failure_message' => $message,
            ]);
    }

    private function storeReusablePaymentMethod(
        Workspace $workspace,
        PaymentGatewayName $gateway,
        ?PaymentMethodData $data,
    ): ?PaymentMethod {
        if (! $data instanceof PaymentMethodData || ! $data->reusable) {
            return null;
        }

        $authorizationHash = hash('sha256', $data->signature ?? $data->authorizationCode);

        PaymentMethod::query()
            ->where('workspace_id', $workspace->getKey())
            ->where('gateway', $gateway)
            ->where('is_default', true)
            ->where('authorization_hash', '!=', $authorizationHash)
            ->update(['is_default' => false]);

        return PaymentMethod::query()->updateOrCreate(
            [
                'workspace_id' => $workspace->getKey(),
                'gateway' => $gateway,
                'authorization_hash' => $authorizationHash,
            ],
            [
                'type' => $data->channel,
                'authorization_code' => $data->authorizationCode,
                'authorization_data' => $data->authorizationData,
                'customer_code' => $data->customerCode,
                'email' => $data->email,
                'brand' => $data->cardType,
                'last_four' => $data->lastFour,
                'exp_month' => $data->expiryMonth,
                'exp_year' => $data->expiryYear,
                'reusable' => true,
                'is_default' => true,
                'last_used_at' => now(),
            ],
        );
    }

    private function recordLedgerTransaction(
        Payment $payment,
        Workspace $workspace,
        Model $transactable,
    ): void {
        Transaction::query()->create([
            'payment_id' => $payment->getKey(),
            'owner_type' => $workspace->getMorphClass(),
            'owner_id' => $workspace->getKey(),
            'transactable_type' => $transactable->getMorphClass(),
            'transactable_id' => $transactable->getKey(),
            'amount' => (new Money($payment->amount_minor, $payment->currency))->toMajorAmount(),
            'currency' => $payment->currency,
            'reference' => $payment->reference,
            'type' => match ($payment->purpose) {
                PaymentPurpose::WALLET_TOP_UP => TransactionTypes::TOPUP,
                PaymentPurpose::SUBSCRIPTION => TransactionTypes::SUBSCRIPTION,
                PaymentPurpose::CAPACITY_PURCHASE => TransactionTypes::CAPACITY_PURCHASE,
                PaymentPurpose::PLAN_UPGRADE => TransactionTypes::PLAN_CHANGE,
            },
            'status' => TransactionStatus::COMPLETED,
            'flow' => $payment->purpose === PaymentPurpose::WALLET_TOP_UP
                ? TransactionFlow::CREDIT
                : TransactionFlow::DEBIT,
            'meta' => [
                'description' => match ($payment->purpose) {
                    PaymentPurpose::WALLET_TOP_UP => 'Wallet top-up',
                    PaymentPurpose::SUBSCRIPTION => 'Plan subscription payment',
                    PaymentPurpose::CAPACITY_PURCHASE => 'Additional capacity purchase',
                    PaymentPurpose::PLAN_UPGRADE => 'Prorated plan upgrade',
                },
                'gateway' => $payment->gateway->value,
            ],
        ]);
    }
}
