<?php

namespace App\Enums\WorkspaceMembers;

enum WorkspaceMemberRole: string
{
    case Owner = 'owner';
    case Administrator = 'administrator';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Administrator => 'Administrator',
            self::Viewer => 'Viewer',
        };
    }

    public function canManage(): bool
    {
        return $this !== self::Viewer;
    }
}
