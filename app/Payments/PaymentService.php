<?php

namespace App\Payments;

use App\Contracts\Payments\RecurringPaymentGateway;
use App\DTOs\Payments\CheckoutSession;
use App\DTOs\Payments\GatewayWebhook;
use App\DTOs\Payments\InitializePaymentData;
use App\DTOs\Payments\PaymentVerification;
use App\DTOs\Payments\RecurringChargeData;
use App\Enums\Payments\PaymentGatewayName;
use App\Exceptions\Payments\UnsupportedGatewayOperationException;

final readonly class PaymentService
{
    public function __construct(private PaymentGatewayRegistry $gateways) {}

    public function gatewayName(?PaymentGatewayName $gateway = null): PaymentGatewayName
    {
        return $this->gateways->gatewayName($gateway);
    }

    public function initialize(
        InitializePaymentData $data,
        ?PaymentGatewayName $gateway = null,
    ): CheckoutSession {
        return $this->gateways->resolve($gateway)->initialize($data);
    }

    public function verify(
        string $reference,
        ?PaymentGatewayName $gateway = null,
    ): PaymentVerification {
        return $this->gateways->resolve($gateway)->verify($reference);
    }

    public function charge(
        RecurringChargeData $data,
        ?PaymentGatewayName $gateway = null,
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
        ?PaymentGatewayName $gateway = null,
    ): GatewayWebhook {
        return $this->gateways->resolve($gateway)->parseWebhook($rawPayload, $headers);
    }
}
