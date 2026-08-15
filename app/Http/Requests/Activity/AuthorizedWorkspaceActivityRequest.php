<?php

namespace App\Http\Requests\Activity;

use App\Enums\Account\Roles;
use App\Enums\Account\Status;
use App\Enums\AccountTypes;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class AuthorizedWorkspaceActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $workspace = Workspace::current();

        if (! $user instanceof User
            || ! $workspace instanceof Workspace
            || $user->status !== Status::ACTIVE
            || ! in_array($workspace->type, [AccountTypes::INDIVIDUAL, AccountTypes::BUSINESS], true)) {
            return false;
        }

        if ($user->role === Roles::SUPER_ADMIN) {
            return true;
        }

        return $user->workspaces()
            ->whereKey($workspace->getKey())
            ->wherePivot('status', Status::ACTIVE->value)
            ->exists();
    }

    public function workspace(): Workspace
    {
        return Workspace::current()
            ?? throw new NotFoundHttpException('No current workspace is available.');
    }

    public function activityUser(): User
    {
        $user = $this->user();

        return $user instanceof User
            ? $user
            : throw new NotFoundHttpException('No authenticated user is available.');
    }
}
