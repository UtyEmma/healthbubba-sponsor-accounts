<?php

namespace App\Http\Requests\Funding;

use App\Enums\Account\Roles;
use App\Enums\Account\Status;
use App\Enums\AccountTypes;
use App\Enums\WorkspaceMembers\WorkspaceMemberRole;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class AuthorizedInstitutionalFundingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $workspace = Workspace::current();

        if (! $user instanceof User
            || ! $workspace instanceof Workspace
            || $workspace->type !== AccountTypes::INSTITUTION
            || $user->status !== Status::ACTIVE) {
            return false;
        }

        if ($user->role === Roles::SUPER_ADMIN) {
            return true;
        }

        return $user->workspaces()
            ->whereKey($workspace->getKey())
            ->wherePivot('status', Status::ACTIVE->value)
            ->wherePivotIn('role', [WorkspaceMemberRole::Owner->value, WorkspaceMemberRole::Administrator->value])
            ->exists();
    }

    public function workspace(): Workspace
    {
        return Workspace::current()
            ?? throw new NotFoundHttpException('No current workspace is available.');
    }
}
