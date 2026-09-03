<?php

namespace App\Enums\Auth;

enum AccountAvailabilityStatus: string
{
    case NewIdentity = 'new_identity';
    case ExistingIdentity = 'existing_identity';
    case OwnedWorkspace = 'owned_workspace';
    case MemberWorkspace = 'member_workspace';
}
