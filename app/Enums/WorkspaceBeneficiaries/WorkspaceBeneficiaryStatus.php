<?php

namespace App\Enums\WorkspaceBeneficiaries;

enum WorkspaceBeneficiaryStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';
    case Revoked = 'revoked';
    case Declined = 'declined';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
}
