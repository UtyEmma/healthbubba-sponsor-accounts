<?php

namespace App\Enums\Transactions;

enum TransactionFlow: string
{
    case DEBIT = 'debit';
    case CREDIT = 'credit';
}
