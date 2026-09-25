<?php

namespace App\Enums\CapacityPurchases;

use App\Enums\Payments\PaymentGatewayName;

enum CapacityPaymentSource: string
{
    case WALLET = 'wallet';
    case PAYSTACK = 'paystack';
    case STRIPE = 'stripe';

    public function gateway(): ?PaymentGatewayName
    {
        return match ($this) {
            self::WALLET => null,
            self::PAYSTACK => PaymentGatewayName::PAYSTACK,
            self::STRIPE => PaymentGatewayName::STRIPE,
        };
    }
}
