<?php

namespace App\Http\Controllers\Activity;

use App\Actions\Activity\MarkWorkspaceActivitiesReadAction;
use App\Http\Requests\Activity\MarkWorkspaceActivitiesReadRequest;
use Illuminate\Http\RedirectResponse;

final readonly class MarkWorkspaceActivitiesReadController
{
    public function __construct(private MarkWorkspaceActivitiesReadAction $markRead) {}

    public function __invoke(MarkWorkspaceActivitiesReadRequest $request): RedirectResponse
    {
        $this->markRead->execute($request->workspace(), $request->activityUser());

        return back();
    }
}
