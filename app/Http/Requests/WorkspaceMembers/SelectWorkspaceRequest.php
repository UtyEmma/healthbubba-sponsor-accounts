<?php

namespace App\Http\Requests\WorkspaceMembers;

use App\Enums\WorkspaceMembers\WorkspaceMemberStatus;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Http\FormRequest;

final class SelectWorkspaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $workspace = $this->route('workspace');

        return $user !== null
            && $workspace instanceof Workspace
            && WorkspaceMember::query()->whereBelongsTo($user)->whereBelongsTo($workspace)
                ->where('status', WorkspaceMemberStatus::Active)->exists();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }
}
