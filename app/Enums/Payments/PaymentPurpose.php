<?php

namespace App\Enums\Payments;

enum PaymentPurpose: string
{
    case WALLET_TOP_UP = 'wallet_top_up';
    case SUBSCRIPTION = 'subscription';
    case CAPACITY_PURCHASE = 'capacity_purchase';
}
