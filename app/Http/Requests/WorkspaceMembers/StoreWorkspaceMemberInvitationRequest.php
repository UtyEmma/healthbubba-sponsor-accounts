<?php

namespace App\Http\Requests\WorkspaceMembers;

use App\DTOs\WorkspaceMembers\InviteWorkspaceMemberData;
use App\Enums\WorkspaceMembers\WorkspaceMemberRole;
use Illuminate\Validation\Rule;

final class StoreWorkspaceMemberInvitationRequest extends AuthorizedWorkspaceTeamRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'role' => ['required', Rule::in([WorkspaceMemberRole::Administrator->value, WorkspaceMemberRole::Viewer->value])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->string('name')->squish()->toString(),
            'email' => $this->string('email')->trim()->lower()->toString(),
        ]);
    }

    public function invitationData(): InviteWorkspaceMemberData
    {
        return new InviteWorkspaceMemberData(
            name: $this->validated('name'),
            email: $this->validated('email'),
            role: WorkspaceMemberRole::from($this->validated('role')),
        );
    }
}
