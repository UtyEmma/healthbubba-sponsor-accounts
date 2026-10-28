<?php

namespace App\Enums\Subscriptions;

enum SubscriptionFundingSource: string
{
    case CustomerPaid = 'customer_paid';
    case Wallet = 'wallet';
    case Complimentary = 'complimentary';

    public function label(): string
    {
        return match ($this) {
            self::CustomerPaid => 'Customer paid',
            self::Wallet => 'Workspace wallet',
            self::Complimentary => 'Complimentary',
        };
    }
}
