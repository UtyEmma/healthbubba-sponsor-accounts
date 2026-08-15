<?php

namespace App\Http\Controllers\WorkspaceMembers;

use App\Actions\WorkspaceMembers\ExpireWorkspaceMemberInvitationsAction;
use App\Http\Requests\WorkspaceMembers\IndexWorkspaceTeamRequest;
use App\Http\Resources\WorkspaceMemberResource;
use App\Queries\WorkspaceMembers\WorkspaceTeamQuery;
use App\Services\WorkspaceMembers\WorkspaceMemberAccessService;
use Inertia\Inertia;
use Inertia\Response;

final readonly class WorkspaceTeamIndexController
{
    public function __construct(
        private WorkspaceTeamQuery $team,
        private ExpireWorkspaceMemberInvitationsAction $expire,
        private WorkspaceMemberAccessService $access,
    ) {}

    public function __invoke(IndexWorkspaceTeamRequest $request): Response
    {
        $workspace = $request->workspace();
        $this->expire->execute($workspace);

        return Inertia::render('institutional-sponsor/team/index', [
            'members' => WorkspaceMemberResource::collection($this->team->paginate($workspace)),
            'canManage' => $this->access->canManage($request->teamUser(), $workspace),
        ]);
    }
}
