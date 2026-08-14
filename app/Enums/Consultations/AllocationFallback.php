<?php

namespace App\Enums\Consultations;

enum AllocationFallback: string
{
    case BENEFICIARY_WALLET = 'beneficiary_wallet';
    case CARD_PAYMENT = 'card_payment';
}
