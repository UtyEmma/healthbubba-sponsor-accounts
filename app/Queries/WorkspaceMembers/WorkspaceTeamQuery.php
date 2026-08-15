<?php

namespace App\Queries\WorkspaceMembers;

use App\Enums\WorkspaceMembers\WorkspaceMemberRole;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class WorkspaceTeamQuery
{
    /** @return LengthAwarePaginator<int, WorkspaceMember> */
    public function paginate(Workspace $workspace): LengthAwarePaginator
    {
        return WorkspaceMember::query()
            ->with('user:id,name,email')
            ->whereBelongsTo($workspace)
            ->orderByRaw('CASE WHEN role = ? THEN 0 ELSE 1 END', [WorkspaceMemberRole::Owner->value])
            ->latest('id')
            ->paginate(20)
            ->withQueryString();
    }
}
