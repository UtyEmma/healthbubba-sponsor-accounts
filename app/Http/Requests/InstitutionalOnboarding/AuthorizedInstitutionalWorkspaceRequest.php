<?php

namespace App\Http\Requests\InstitutionalOnboarding;

use App\Enums\Account\Roles;
use App\Enums\Account\Status;
use App\Enums\AccountTypes;
use App\Enums\WorkspaceMembers\WorkspaceMemberStatus;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class AuthorizedInstitutionalWorkspaceRequest extends FormRequest
{
    protected bool $ownerOnly = false;

    public function authorize(): bool
    {
        $user = $this->user();
        $workspace = Workspace::current();

        if (! $user instanceof User
            || ! $workspace instanceof Workspace
            || $user->status !== Status::ACTIVE
            || $workspace->type !== AccountTypes::INSTITUTION) {
            return false;
        }

        if (! $this->ownerOnly && $user->role === Roles::SUPER_ADMIN) {
            return true;
        }

        $membership = WorkspaceMember::query()
            ->whereBelongsTo($workspace)
            ->whereBelongsTo($user)
            ->where('status', WorkspaceMemberStatus::Active)
            ->first();

        return $membership instanceof WorkspaceMember
            && (! $this->ownerOnly || $membership->isOwner());
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }

    public function workspace(): Workspace
    {
        return Workspace::current()
            ?? throw new NotFoundHttpException('No current institutional workspace is available.');
    }

    public function onboardingUser(): User
    {
        $user = $this->user();

        return $user instanceof User
            ? $user
            : throw new NotFoundHttpException('No authenticated user is available.');
    }
}
