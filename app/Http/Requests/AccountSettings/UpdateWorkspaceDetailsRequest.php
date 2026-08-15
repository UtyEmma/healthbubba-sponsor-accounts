<?php

namespace App\Http\Requests\AccountSettings;

use App\Enums\Account\Roles;
use App\Enums\Account\Status;
use App\Enums\WorkspaceMembers\WorkspaceMemberRole;
use App\Enums\AccountTypes;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class UpdateWorkspaceDetailsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $workspace = Workspace::current();

        if (! $user instanceof User || ! $workspace instanceof Workspace || $user->status !== Status::ACTIVE) {
            return false;
        }

        if (! in_array($workspace->type, [AccountTypes::BUSINESS, AccountTypes::INSTITUTION], true)) {
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

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'type' => ['prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $description = $this->string('description')->trim()->toString();

        $this->merge([
            'name' => $this->string('name')->squish()->toString(),
            'description' => $description === '' ? null : $description,
        ]);
    }

    public function workspace(): Workspace
    {
        return Workspace::current()
            ?? throw new NotFoundHttpException('No current workspace is available.');
    }
}
