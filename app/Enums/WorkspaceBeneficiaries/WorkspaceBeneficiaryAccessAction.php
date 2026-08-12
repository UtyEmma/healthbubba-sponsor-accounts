<?php

namespace App\Enums\WorkspaceBeneficiaries;

enum WorkspaceBeneficiaryAccessAction: string
{
    case Suspend = 'suspend';
    case Restore = 'restore';
    case Revoke = 'revoke';
}
