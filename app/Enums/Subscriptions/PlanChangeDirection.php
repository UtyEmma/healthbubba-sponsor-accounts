<?php

namespace App\Enums\Subscriptions;

enum PlanChangeDirection: string
{
    case UPGRADE = 'upgrade';
    case DOWNGRADE = 'downgrade';
}
