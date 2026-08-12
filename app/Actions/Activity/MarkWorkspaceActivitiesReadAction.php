<?php

namespace App\Actions\Activity;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceActivityRead;
use App\Services\Activity\WorkspaceActivityQuery;

final readonly class MarkWorkspaceActivitiesReadAction
{
    public function __construct(private WorkspaceActivityQuery $activities) {}

    public function execute(Workspace $workspace, User $user): int
    {
        $notificationIds = $this->activities->unreadIds($workspace, $user);
        $now = now();
        $inserted = 0;

        foreach ($notificationIds->chunk(500) as $chunk) {
            $rows = $chunk->map(fn (string $notificationId): array => [
                'notification_id' => $notificationId,
                'user_id' => $user->getKey(),
                'read_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            $inserted += WorkspaceActivityRead::query()->insertOrIgnore($rows);
        }

        return $inserted;
    }
}
