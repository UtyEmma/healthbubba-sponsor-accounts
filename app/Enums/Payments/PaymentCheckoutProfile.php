<?php

namespace App\Enums\Payments;

enum PaymentCheckoutProfile: string
{
    case FLEXIBLE = 'flexible';
    case CARD = 'card';
    case RECURRING_CARD = 'recurring_card';
}
