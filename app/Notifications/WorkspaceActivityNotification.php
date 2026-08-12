<?php

namespace App\Notifications;

use App\DTOs\Activity\WorkspaceActivityData;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class WorkspaceActivityNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly WorkspaceActivityData $activity) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return $this->activity->toArray();
    }
}
