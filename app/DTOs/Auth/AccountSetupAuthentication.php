<?php

namespace App\DTOs\Auth;

use App\Models\User;
use App\Models\WorkspaceMember;

final readonly class AccountSetupAuthentication
{
    public function __construct(
        public User $user,
        public ?WorkspaceMember $activeMembership,
    ) {}
}
