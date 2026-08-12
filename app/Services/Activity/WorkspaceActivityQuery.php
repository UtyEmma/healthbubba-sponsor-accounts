<?php

namespace App\Services\Activity;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceActivityRead;
use App\Notifications\WorkspaceActivityNotification;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class WorkspaceActivityQuery
{
    /** @return LengthAwarePaginator<int, DatabaseNotification> */
    public function paginate(Workspace $workspace, User $user, int $perPage = 20): LengthAwarePaginator
    {
        return $this->withReadState($workspace, $user)
            ->latest((new DatabaseNotification)->qualifyColumn('created_at'))
            ->paginate($perPage)
            ->withQueryString();
    }

    /** @return Collection<int, DatabaseNotification> */
    public function recent(Workspace $workspace, User $user, int $limit = 5): Collection
    {
        return $this->withReadState($workspace, $user)
            ->latest((new DatabaseNotification)->qualifyColumn('created_at'))
            ->limit($limit)
            ->get();
    }

    public function unreadCount(Workspace $workspace, User $user): int
    {
        return $this->unread($workspace, $user)->count();
    }

    /** @return Collection<int, string> */
    public function unreadIds(Workspace $workspace, User $user): Collection
    {
        return $this->unread($workspace, $user)
            ->pluck((new DatabaseNotification)->qualifyColumn('id'));
    }

    /** @return MorphMany<DatabaseNotification, Workspace> */
    private function withReadState(Workspace $workspace, User $user): MorphMany
    {
        return $this->base($workspace)
            ->select('notifications.*')
            ->selectSub(
                WorkspaceActivityRead::query()
                    ->selectRaw('1')
                    ->whereColumn('workspace_activity_reads.notification_id', 'notifications.id')
                    ->where('workspace_activity_reads.user_id', $user->getKey())
                    ->limit(1),
                'is_read',
            );
    }

    /** @return MorphMany<DatabaseNotification, Workspace> */
    private function unread(Workspace $workspace, User $user): MorphMany
    {
        $notificationsTable = (new DatabaseNotification)->getTable();
        $readsTable = (new WorkspaceActivityRead)->getTable();

        return $this->base($workspace)->whereNotExists(function ($query) use (
            $notificationsTable,
            $readsTable,
            $user,
        ): void {
            $query->selectRaw('1')
                ->from($readsTable)
                ->whereColumn("{$readsTable}.notification_id", "{$notificationsTable}.id")
                ->where("{$readsTable}.user_id", $user->getKey());
        });
    }

    /** @return MorphMany<DatabaseNotification, Workspace> */
    private function base(Workspace $workspace): MorphMany
    {
        return $workspace->notifications()
            ->where('type', WorkspaceActivityNotification::class);
    }
}
