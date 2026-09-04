<?php

namespace App\Enums\Payments;

enum SubscriptionPaymentSource: string
{
    case WALLET = 'wallet';
    case PAYSTACK = 'paystack';
}
