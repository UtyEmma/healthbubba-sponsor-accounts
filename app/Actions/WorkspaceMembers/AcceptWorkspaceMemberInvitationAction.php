<?php

namespace App\Actions\WorkspaceMembers;

use App\Enums\Account\Roles;
use App\Enums\WorkspaceMembers\WorkspaceMemberStatus;
use App\Models\User;
use App\Models\WorkspaceMember;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class AcceptWorkspaceMemberInvitationAction
{
    public function execute(WorkspaceMember $invitation, ?User $authenticatedUser, ?string $password): WorkspaceMember
    {
        return DB::transaction(function () use ($invitation, $authenticatedUser, $password): WorkspaceMember {
            $member = WorkspaceMember::query()->whereKey($invitation->getKey())->lockForUpdate()->firstOrFail();

            if ($member->hasExpired()) {
                $member->update(['status' => WorkspaceMemberStatus::Expired]);

                return $member;
            }

            if (! $member->isInvited()) {
                return $member;
            }

            $existingUser = User::query()->where('email', $member->email)->lockForUpdate()->first();

            if ($existingUser !== null) {
                if ($authenticatedUser?->isNot($existingUser) ?? true) {
                    throw ValidationException::withMessages(['email' => 'Sign in with the invited email address to accept this invitation.']);
                }

                $user = $existingUser;
            } else {
                if ($password === null) {
                    throw ValidationException::withMessages(['password' => 'Create a password to accept this invitation.']);
                }

                $user = User::query()->create([
                    'name' => $member->name,
                    'email' => $member->email,
                    'password' => Hash::make($password),
                    'role' => Roles::USER,
                ]);
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            $duplicate = WorkspaceMember::query()
                ->where('workspace_id', $member->workspace_id)
                ->where('user_id', $user->getKey())
                ->whereKeyNot($member->getKey())
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages(['email' => 'This account already belongs to the workspace.']);
            }

            $now = now();
            $member->update([
                'user_id' => $user->getKey(),
                'name' => $user->name,
                'status' => WorkspaceMemberStatus::Active,
                'accepted_at' => $now,
                'last_selected_at' => $now,
            ]);

            return $member->load('user', 'workspace');
        });
    }
}
