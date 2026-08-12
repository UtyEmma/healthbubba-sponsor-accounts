<?php

namespace App\Http\Requests\WorkspaceBeneficiaries;

use App\Enums\Account\Roles;
use App\Enums\Account\Status;
use App\Enums\AccountTypes;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceBeneficiary;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class AuthorizedWorkspaceBeneficiaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $workspace = Workspace::current();

        if (! $user instanceof User || ! $workspace instanceof Workspace || $user->status !== Status::ACTIVE) {
            return false;
        }

        if (! in_array($workspace->type, [AccountTypes::INDIVIDUAL, AccountTypes::BUSINESS], true)) {
            return false;
        }

        $invitation = $this->route('workspaceBeneficiary');

        if ($invitation instanceof WorkspaceBeneficiary && $invitation->workspace_id !== $workspace->getKey()) {
            return false;
        }

        if ($user->role === Roles::SUPER_ADMIN) {
            return true;
        }

        return $user->workspaces()
            ->whereKey($workspace->getKey())
            ->wherePivot('status', Status::ACTIVE->value)
            ->wherePivotIn('role', [Roles::ADMIN->value, Roles::SUPER_ADMIN->value])
            ->exists();
    }

    public function workspace(): Workspace
    {
        return Workspace::current() ?? throw new NotFoundHttpException('No current workspace is available.');
    }

    public function invitation(): WorkspaceBeneficiary
    {
        $invitation = $this->route('workspaceBeneficiary');

        if (! $invitation instanceof WorkspaceBeneficiary) {
            throw new NotFoundHttpException('Invitation not found.');
        }

        return $invitation;
    }
}
