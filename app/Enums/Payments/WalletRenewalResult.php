<?php

namespace App\Enums\Payments;

enum WalletRenewalResult: string
{
    case PAID = 'paid';
    case INSUFFICIENT = 'insufficient';
    case NOT_DUE = 'not_due';
}
