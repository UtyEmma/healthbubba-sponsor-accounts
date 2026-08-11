<?php

namespace App\Exceptions\Payments;

use App\Enums\Payments\PaymentGatewayName;
use Throwable;

final class GatewayRequestException extends PaymentException
{
    public function __construct(
        public readonly PaymentGatewayName $gateway,
        string $message,
        public readonly ?string $reference = null,
        public readonly ?int $httpStatus = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, previous: $previous);
    }

    /** @return array{payment_gateway: string, payment_reference: string|null, http_status: int|null} */
    public function context(): array
    {
        return [
            'payment_gateway' => $this->gateway->value,
            'payment_reference' => $this->reference,
            'http_status' => $this->httpStatus,
        ];
    }
}
