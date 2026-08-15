<?php

namespace App\DTOs\WorkspaceMembers;

use App\Enums\WorkspaceMembers\WorkspaceMemberRole;

final readonly class InviteWorkspaceMemberData
{
    public function __construct(
        public string $name,
        public string $email,
        public WorkspaceMemberRole $role,
    ) {}
}
