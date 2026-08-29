<?php

namespace App\Actions\Payments;

use App\DTOs\Payments\CheckoutSession;
use App\DTOs\Payments\InitializePaymentData;
use App\DTOs\Payments\StartWalletFundingData;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentStatus;
use App\Exceptions\Payments\PaymentException;
use App\Exceptions\Payments\PaymentVerificationFailed;
use App\Models\Payment;
use App\Payments\PaymentService;
use App\Support\Payments\PaymentReferenceGenerator;

final readonly class StartWalletFundingAction
{
    public function __construct(
        private PaymentService $payments,
        private PaymentReferenceGenerator $references,
        private FailPaymentAction $failPayment,
    ) {}

    public function execute(StartWalletFundingData $data): CheckoutSession
    {
        $gateway = $this->payments->gatewayName($data->gateway);
        $wallet = $data->workspace->wallet()->firstOrCreate([], [
            'balance' => '0.00',
            'currency' => $data->amount->currency,
        ]);

        if ($wallet->currency !== $data->amount->currency) {
            throw new PaymentVerificationFailed('The wallet currency does not match the payment currency.');
        }

        $payment = Payment::query()->create([
            'workspace_id' => $data->workspace->getKey(),
            'user_id' => $data->user->getKey(),
            'payable_type' => $wallet->getMorphClass(),
            'payable_id' => $wallet->getKey(),
            'purpose' => PaymentPurpose::WALLET_TOP_UP,
            'status' => PaymentStatus::PENDING,
            'gateway' => $gateway,
            'reference' => $this->references->generate(PaymentPurpose::WALLET_TOP_UP),
            'amount_minor' => $data->amount->amountInMinorUnits,
            'currency' => $data->amount->currency,
            'metadata' => [
                'email' => $data->user->email,
                'workspace_id' => $data->workspace->getKey(),
                'purpose' => PaymentPurpose::WALLET_TOP_UP->value,
                'funding_method' => $data->fundingMethod,
            ],
        ]);

        $metadata = [
            ...$payment->metadata,
            'payment_id' => $payment->getKey(),
        ];

        $payment->update(['metadata' => $metadata]);

        try {
            $session = $this->payments->initialize(
                new InitializePaymentData(
                    amount: $data->amount,
                    email: $data->user->email,
                    reference: $payment->reference,
                    callbackUrl: $data->callbackUrl,
                    metadata: $metadata,
                    channels: $data->channels,
                ),
                $gateway,
            );
        } catch (PaymentException $exception) {
            $this->failPayment->execute($payment, 'initialization_failed', $exception->getMessage());

            throw $exception;
        }

        $payment->update([
            'provider_reference' => $session->reference,
            'provider_metadata' => ['gateway' => $session->gateway->value],
            'initialized_at' => now(),
        ]);

        return $session;
    }
}
