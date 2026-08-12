<?php

namespace App\DTOs\Activity;

use App\Enums\Activity\WorkspaceActivityActorType;
use App\Models\User;
use App\Models\WorkspaceBeneficiary;

final readonly class WorkspaceActivityActor
{
    public function __construct(
        public WorkspaceActivityActorType $type,
        public string $name,
        public ?int $id = null,
        public ?int $userId = null,
    ) {}

    public static function user(User $user): self
    {
        return new self(
            type: WorkspaceActivityActorType::User,
            name: $user->name,
            id: (int) $user->getKey(),
            userId: (int) $user->getKey(),
        );
    }

    public static function beneficiary(WorkspaceBeneficiary $beneficiary): self
    {
        return new self(
            type: WorkspaceActivityActorType::Beneficiary,
            name: trim("{$beneficiary->first_name} {$beneficiary->last_name}"),
            id: (int) $beneficiary->getKey(),
        );
    }

    public static function system(): self
    {
        return new self(
            type: WorkspaceActivityActorType::System,
            name: 'HealthBubba System',
        );
    }

    /** @return array{type: string, id: int|null, user_id: int|null, name: string} */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'id' => $this->id,
            'user_id' => $this->userId,
            'name' => $this->name,
        ];
    }
}
