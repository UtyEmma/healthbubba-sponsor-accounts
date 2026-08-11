<?php

namespace App\Exceptions\Payments;

use App\Enums\Payments\PaymentGatewayName;

final class PaymentGatewayNotConfiguredException extends PaymentException
{
    public function __construct(public readonly PaymentGatewayName $gateway)
    {
        parent::__construct("The [{$gateway->value}] payment gateway is not configured.");
    }
}
