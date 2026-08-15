<?php

namespace App\Http\Requests\WorkspaceMembers;

use App\Enums\Account\Roles;
use App\Enums\Account\Status;
use App\Enums\WorkspaceMembers\WorkspaceMemberStatus;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class AuthorizedWorkspaceViewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $workspace = Workspace::current();

        if (! $user instanceof User || ! $workspace instanceof Workspace || $user->status !== Status::ACTIVE) {
            return false;
        }

        return $user->role === Roles::SUPER_ADMIN
            || WorkspaceMember::query()->whereBelongsTo($user)->whereBelongsTo($workspace)
                ->where('status', WorkspaceMemberStatus::Active)->exists();
    }

    public function workspace(): Workspace
    {
        return Workspace::current() ?? throw new NotFoundHttpException('No current workspace is available.');
    }
}
