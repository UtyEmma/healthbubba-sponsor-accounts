<?php

namespace App\Exceptions\Payments;

use App\Enums\Payments\PaymentGatewayName;

final class UnsupportedGatewayOperationException extends PaymentException
{
    public function __construct(
        public readonly PaymentGatewayName $gateway,
        public readonly string $operation,
    ) {
        parent::__construct("The [{$gateway->value}] payment gateway does not support [{$operation}].");
    }
}
