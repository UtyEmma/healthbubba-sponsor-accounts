<?php

namespace App\Enums\Transactions;

enum TransactionTypes: string
{
    case TOPUP = 'topup';
    case SUBSCRIPTION = 'subscription';
    case CAPACITY_PURCHASE = 'capacity_purchase';
    case PLAN_CHANGE = 'plan_change';
}
