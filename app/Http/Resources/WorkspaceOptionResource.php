<?php

namespace App\Http\Resources;

use App\Models\WorkspaceMember;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkspaceMember */
final class WorkspaceOptionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->workspace_id,
            'name' => $this->workspace->name,
            'type' => $this->workspace->type->value,
            'role' => $this->role->value,
            'roleLabel' => $this->role->label(),
            'isCurrent' => (int) $request->session()->get('current_workspace_id') === $this->workspace_id,
        ];
    }
}
