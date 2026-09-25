<?php

namespace App\Enums\Payments;

enum PaymentGatewayName: string
{
    case PAYSTACK = 'paystack';
    case STRIPE = 'stripe';

    public function label(): string
    {
        return match ($this) {
            self::PAYSTACK => 'Paystack',
            self::STRIPE => 'Stripe',
        };
    }
}
