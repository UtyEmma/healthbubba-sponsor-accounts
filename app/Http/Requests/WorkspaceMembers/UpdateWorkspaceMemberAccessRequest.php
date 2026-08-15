<?php

namespace App\Http\Requests\WorkspaceMembers;

final class UpdateWorkspaceMemberAccessRequest extends ManageWorkspaceMemberRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['enabled' => ['required', 'boolean']];
    }
}
