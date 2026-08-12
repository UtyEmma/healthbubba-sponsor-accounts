<?php

namespace App\DTOs\Activity;

use App\Enums\Activity\WorkspaceActivityType;

final readonly class WorkspaceActivityData
{
    /** @param array<string, scalar|null> $context */
    public function __construct(
        public WorkspaceActivityType $type,
        public string $title,
        public WorkspaceActivityActor $actor,
        public string $subjectType,
        public int|string|null $subjectId,
        public string $subjectName,
        public ?string $description = null,
        public array $context = [],
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema_version' => 1,
            'event' => $this->type->value,
            'category' => $this->type->category(),
            'title' => $this->title,
            'description' => $this->description,
            'icon' => $this->type->icon(),
            'tone' => $this->type->tone(),
            'actor' => $this->actor->toArray(),
            'subject' => [
                'type' => $this->subjectType,
                'id' => $this->subjectId,
                'name' => $this->subjectName,
            ],
            'context' => $this->context,
        ];
    }
}
