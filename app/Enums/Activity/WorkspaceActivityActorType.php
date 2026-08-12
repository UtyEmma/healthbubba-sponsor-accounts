<?php

namespace App\Enums\Activity;

enum WorkspaceActivityActorType: string
{
    case User = 'user';
    case Beneficiary = 'beneficiary';
    case System = 'system';
}
