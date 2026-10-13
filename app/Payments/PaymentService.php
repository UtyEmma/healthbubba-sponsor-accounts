<?php

namespace App\Payments;

use App\Contracts\Payments\RecurringPaymentGateway;
use App\DTOs\Payments\CheckoutSession;
use App\DTOs\Payments\GatewayWebhook;
use App\DTOs\Payments\InitializePaymentData;
use App\DTOs\Payments\PaymentVerification;
use App\DTOs\Payments\RecurringChargeData;
use App\Enums\AccountTypes;
use App\Enums\Payments\PaymentGatewayName;
use App\Exceptions\Payments\UnsupportedGatewayOperationException;

final readonly class PaymentService
{
    public function __construct(
        private PaymentGatewayRegistry $gateways,
        private ActivePaymentGatewayResolver $activeGateway,
    ) {}

    public function gatewayName(
        AccountTypes $accountType,
        ?PaymentGatewayName $gateway = null,
    ): PaymentGatewayName {
        return $this->gateways->gatewayName($gateway ?? $this->activeGateway->resolve($accountType));
    }

    public function initialize(
        InitializePaymentData $data,
        PaymentGatewayName $gateway,
    ): CheckoutSession {
        return $this->gateways
            ->resolve($gateway)
            ->initialize($data);
    }

    public function verify(
        string $reference,
        PaymentGatewayName $gateway,
        ?string $providerReference = null,
    ): PaymentVerification {
        return $this->gateways
            ->resolve($gateway)
            ->verify($reference, $providerReference);
    }

    public function charge(
        RecurringChargeData $data,
        PaymentGatewayName $gateway,
    ): PaymentVerification {
        $resolvedGateway = $this->gateways->resolve($gateway);

        if (! $resolvedGateway instanceof RecurringPaymentGateway) {
            throw new UnsupportedGatewayOperationException(
                $resolvedGateway->name(),
                'recurring charges',
            );
        }

        return $resolvedGateway->charge($data);
    }

    /** @param array<string, mixed> $headers */
    public function parseWebhook(
        string $rawPayload,
        array $headers,
        PaymentGatewayName $gateway,
    ): GatewayWebhook {
        return $this->gateways
            ->resolve($gateway)
            ->parseWebhook($rawPayload, $headers);
    }
}
