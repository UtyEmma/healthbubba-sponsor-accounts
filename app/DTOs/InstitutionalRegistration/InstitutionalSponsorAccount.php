<?php

namespace App\DTOs\InstitutionalRegistration;

use App\Models\User;
use App\Models\Workspace;

final readonly class InstitutionalSponsorAccount
{
    public function __construct(
        public User $user,
        public Workspace $workspace,
    ) {}
}
