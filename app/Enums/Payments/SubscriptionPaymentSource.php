<?php

namespace App\Enums\Payments;

enum SubscriptionPaymentSource: string
{
    case WALLET = 'wallet';
    case PAYSTACK = 'paystack';
    case STRIPE = 'stripe';

    public function isExternal(): bool
    {
        return $this !== self::WALLET;
    }

    public function gateway(): ?PaymentGatewayName
    {
        return match ($this) {
            self::WALLET => null,
            self::PAYSTACK => PaymentGatewayName::PAYSTACK,
            self::STRIPE => PaymentGatewayName::STRIPE,
        };
    }
}
