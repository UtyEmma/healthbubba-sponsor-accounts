<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Notifications\DatabaseNotification;

/** @mixin DatabaseNotification */
final class WorkspaceActivityResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->data;

        return [
            'id' => (string) $this->getKey(),
            'event' => (string) ($data['event'] ?? ''),
            'category' => (string) ($data['category'] ?? ''),
            'title' => (string) ($data['title'] ?? 'Workspace activity'),
            'description' => is_string($data['description'] ?? null) ? $data['description'] : null,
            'icon' => (string) ($data['icon'] ?? 'circle-dot'),
            'tone' => (string) ($data['tone'] ?? 'neutral'),
            'actor' => $data['actor'] ?? [
                'type' => 'system',
                'id' => null,
                'user_id' => null,
                'name' => 'HealthBubba System',
            ],
            'occurredAt' => $this->created_at?->toISOString(),
            'isUnread' => ! (bool) $this->getAttribute('is_read'),
        ];
    }
}
