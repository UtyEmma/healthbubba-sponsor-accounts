<?php

namespace App\Enums\CapacityPurchases;

enum CapacityPurchaseStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case REQUIRES_REVIEW = 'requires_review';
}
