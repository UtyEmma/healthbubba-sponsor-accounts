<?php

namespace App\Actions\WorkspaceMembers;

use App\Enums\WorkspaceMembers\WorkspaceMemberStatus;
use App\Models\WorkspaceMember;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class ResendWorkspaceMemberInvitationAction
{
    public function __construct(private SendWorkspaceMemberInvitationAction $sendInvitation) {}

    public function execute(WorkspaceMember $invitation): WorkspaceMember
    {
        $member = DB::transaction(function () use ($invitation): WorkspaceMember {
            $member = WorkspaceMember::query()->whereKey($invitation->getKey())->lockForUpdate()->firstOrFail();

            if (in_array($member->status, [WorkspaceMemberStatus::Active, WorkspaceMemberStatus::Disabled], true)) {
                throw ValidationException::withMessages(['invitation' => 'Accepted memberships cannot be resent.']);
            }

            $now = now();
            $member->update([
                'status' => WorkspaceMemberStatus::Invited,
                'invitation_version' => $member->invitation_version + 1,
                'invited_at' => $now,
                'expires_at' => $now->copy()->addDays(7),
                'declined_at' => null,
                'cancelled_at' => null,
            ]);

            return $member;
        });

        $this->sendInvitation->execute($member);

        return $member;
    }
}
