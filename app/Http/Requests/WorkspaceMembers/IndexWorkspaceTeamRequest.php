<?php

namespace App\Http\Requests\WorkspaceMembers;

final class IndexWorkspaceTeamRequest extends AuthorizedWorkspaceTeamRequest
{
    protected bool $requiresManagement = false;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }
}
