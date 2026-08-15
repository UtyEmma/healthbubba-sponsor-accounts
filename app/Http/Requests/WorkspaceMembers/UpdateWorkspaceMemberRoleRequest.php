<?php

namespace App\Http\Requests\WorkspaceMembers;

use App\Enums\WorkspaceMembers\WorkspaceMemberRole;
use Illuminate\Validation\Rule;

final class UpdateWorkspaceMemberRoleRequest extends ManageWorkspaceMemberRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['role' => ['required', Rule::in([WorkspaceMemberRole::Administrator->value, WorkspaceMemberRole::Viewer->value])]];
    }
}
