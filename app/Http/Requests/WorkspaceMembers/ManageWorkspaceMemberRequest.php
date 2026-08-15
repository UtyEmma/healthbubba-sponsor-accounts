<?php

namespace App\Http\Requests\WorkspaceMembers;

class ManageWorkspaceMemberRequest extends AuthorizedWorkspaceTeamRequest
{
    public function authorize(): bool
    {
        return parent::authorize()
            && ! $this->target()->isOwner()
            && $this->target()->user_id !== $this->teamUser()->getKey();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }
}
