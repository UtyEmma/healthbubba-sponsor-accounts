<?php

namespace App\Http\Requests\WorkspaceMembers;

use App\Enums\Account\Roles;
use App\Enums\Account\Status;
use App\Enums\WorkspaceMembers\WorkspaceMemberRole;
use App\Enums\WorkspaceMembers\WorkspaceMemberStatus;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class AuthorizedWorkspaceTeamRequest extends FormRequest
{
    protected bool $requiresManagement = true;

    public function authorize(): bool
    {
        $user = $this->user();
        $workspace = Workspace::current();

        if (! $user instanceof User || ! $workspace instanceof Workspace || $user->status !== Status::ACTIVE) {
            return false;
        }

        $target = $this->route('workspaceMember');
        if ($target instanceof WorkspaceMember && $target->workspace_id !== $workspace->getKey()) {
            return false;
        }

        if ($user->role === Roles::SUPER_ADMIN) {
            return true;
        }

        $member = WorkspaceMember::query()
            ->whereBelongsTo($workspace)
            ->whereBelongsTo($user)
            ->where('status', WorkspaceMemberStatus::Active)
            ->first();

        if ($member === null) {
            return false;
        }

        return ! $this->requiresManagement
            || in_array($member->role, [WorkspaceMemberRole::Owner, WorkspaceMemberRole::Administrator], true);
    }

    public function workspace(): Workspace
    {
        return Workspace::current() ?? throw new NotFoundHttpException('No current workspace is available.');
    }

    public function teamUser(): User
    {
        $user = $this->user();

        return $user instanceof User ? $user : throw new NotFoundHttpException('No authenticated user is available.');
    }

    public function target(): WorkspaceMember
    {
        $member = $this->route('workspaceMember');

        return $member instanceof WorkspaceMember
            ? $member
            : throw new NotFoundHttpException('Team member not found.');
    }
}
