<?php

namespace App\Enums\WorkspaceMembers;

enum WorkspaceMemberStatus: string
{
    case Invited = 'invited';
    case Active = 'active';
    case Disabled = 'disabled';
    case Declined = 'declined';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
