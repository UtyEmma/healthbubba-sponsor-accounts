<?php

namespace App\Actions\WorkspaceMembers;

use App\Mail\WorkspaceTeamInvitationMail;
use App\Models\WorkspaceMember;
use App\Services\WorkspaceMembers\WorkspaceMemberInvitationUrlService;
use Illuminate\Support\Facades\Mail;

final readonly class SendWorkspaceMemberInvitationAction
{
    public function __construct(private WorkspaceMemberInvitationUrlService $urls) {}

    public function execute(WorkspaceMember $member): void
    {
        $member->loadMissing('workspace');

        Mail::to($member->email)->queue(new WorkspaceTeamInvitationMail(
            inviteeName: $member->name,
            workspaceName: $member->workspace->name,
            role: $member->role->label(),
            invitationUrl: $this->urls->review($member),
            expiresAt: $member->expires_at?->timezone(config('app.timezone'))->format('j M Y, g:i A') ?? '',
        ));
    }
}
