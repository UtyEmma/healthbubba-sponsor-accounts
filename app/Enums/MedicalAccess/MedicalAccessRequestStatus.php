<?php

namespace App\Enums\MedicalAccess;

enum MedicalAccessRequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Denied = 'denied';
    case Expired = 'expired';
}
