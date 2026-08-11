<?php

namespace App\Enums\Payments;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case REQUIRES_REVIEW = 'requires_review';
    case SUCCEEDED = 'succeeded';
    case FAILED = 'failed';
}
