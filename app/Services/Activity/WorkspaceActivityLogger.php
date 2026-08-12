<?php

namespace App\Services\Activity;

use App\DTOs\Activity\WorkspaceActivityData;
use App\Models\Workspace;
use App\Notifications\WorkspaceActivityNotification;

final class WorkspaceActivityLogger
{
    public function record(Workspace $workspace, WorkspaceActivityData $activity): void
    {
        $workspace->notify(new WorkspaceActivityNotification($activity));
    }
}
