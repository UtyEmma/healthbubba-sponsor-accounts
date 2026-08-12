<?php

namespace App\Http\Controllers\Activity;

use App\Http\Requests\Activity\IndexWorkspaceActivityRequest;
use App\Http\Resources\WorkspaceActivityResource;
use App\Services\Activity\WorkspaceActivityQuery;
use Inertia\Inertia;
use Inertia\Response;

final readonly class WorkspaceActivityIndexController
{
    public function __construct(private WorkspaceActivityQuery $activities) {}

    public function __invoke(IndexWorkspaceActivityRequest $request): Response
    {
        return Inertia::render('sponsor/activity-log/index', [
            'activities' => WorkspaceActivityResource::collection(
                $this->activities->paginate($request->workspace(), $request->activityUser()),
            ),
        ]);
    }
}
