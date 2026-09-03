<?php

namespace App\DTOs\Auth;

use App\Enums\Auth\AccountAvailabilityStatus;

final readonly class AccountAvailability
{
    public function __construct(
        public AccountAvailabilityStatus $status,
        public bool $canLogin,
        public bool $canSetup,
    ) {}
}
