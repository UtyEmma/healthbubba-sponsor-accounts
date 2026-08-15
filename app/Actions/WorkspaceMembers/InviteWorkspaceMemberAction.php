<?php

namespace App\Actions\WorkspaceMembers;

use App\DTOs\WorkspaceMembers\InviteWorkspaceMemberData;
use App\Enums\WorkspaceMembers\WorkspaceMemberStatus;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class InviteWorkspaceMemberAction
{
    public function __construct(private SendWorkspaceMemberInvitationAction $sendInvitation) {}

    public function execute(Workspace $workspace, User $inviter, InviteWorkspaceMemberData $data): WorkspaceMember
    {
        $member = DB::transaction(function () use ($workspace, $inviter, $data): WorkspaceMember {
            Workspace::query()->whereKey($workspace->getKey())->lockForUpdate()->firstOrFail();

            $existing = WorkspaceMember::query()
                ->whereBelongsTo($workspace)
                ->where('email', $data->email)
                ->lockForUpdate()
                ->first();

            if ($existing !== null && in_array($existing->status, [
                WorkspaceMemberStatus::Invited,
                WorkspaceMemberStatus::Active,
                WorkspaceMemberStatus::Disabled,
            ], true)) {
                throw ValidationException::withMessages(['email' => 'This email is already a member or has a pending invitation.']);
            }

            $now = now();
            $member = $existing ?? new WorkspaceMember([
                'workspace_id' => $workspace->getKey(),
                'public_id' => (string) Str::ulid(),
            ]);

            $member->fill([
                'user_id' => null,
                'invited_by_user_id' => $inviter->getKey(),
                'name' => $data->name,
                'email' => $data->email,
                'role' => $data->role,
                'status' => WorkspaceMemberStatus::Invited,
                'invitation_version' => $existing === null ? 1 : $existing->invitation_version + 1,
                'invited_at' => $now,
                'expires_at' => $now->copy()->addDays(7),
                'accepted_at' => null,
                'declined_at' => null,
                'cancelled_at' => null,
                'disabled_at' => null,
                'last_selected_at' => null,
            ])->save();

            return $member;
        });

        $this->sendInvitation->execute($member);

        return $member;
    }
}
