<?php

namespace App\DTOs\Admin;

use App\DTOs\Workspaces\CreateWorkspaceData;
use App\Enums\Account\Roles;
use App\Enums\Account\Status;

final readonly class ProvisionSponsorAccountData
{
    public function __construct(
        public ?int $existingUserId,
        public string $name,
        public string $email,
        public ?string $phone,
        public Status $status,
        public Roles $role,
        public ?string $password,
        public bool $emailVerified,
        public bool $phoneVerified,
        public bool $accountVerified,
        public bool $sendInvitation,
        public CreateWorkspaceData $workspace,
        public ?AssignWorkspaceSubscriptionData $subscription = null,
    ) {}
}
