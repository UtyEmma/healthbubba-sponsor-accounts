<?php

namespace App\Enums\CapacityPurchases;

enum CapacityPaymentSource: string
{
    case WALLET = 'wallet';
    case PAYSTACK = 'paystack';
}
