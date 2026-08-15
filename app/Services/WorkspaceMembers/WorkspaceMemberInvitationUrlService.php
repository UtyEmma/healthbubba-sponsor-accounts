<?php

namespace App\Services\WorkspaceMembers;

use App\Models\WorkspaceMember;
use Illuminate\Support\Facades\URL;

final class WorkspaceMemberInvitationUrlService
{
    public function review(WorkspaceMember $member): string
    {
        return URL::temporarySignedRoute(
            'team-invitations.show',
            $member->expires_at,
            ['workspaceMember' => $member->public_id, 'version' => $member->invitation_version],
        );
    }

    public function accept(WorkspaceMember $member): string
    {
        return URL::temporarySignedRoute(
            'team-invitations.accept',
            $member->expires_at,
            ['workspaceMember' => $member->public_id, 'version' => $member->invitation_version],
        );
    }

    public function decline(WorkspaceMember $member): string
    {
        return URL::temporarySignedRoute(
            'team-invitations.decline',
            $member->expires_at,
            ['workspaceMember' => $member->public_id, 'version' => $member->invitation_version],
        );
    }
}
