<?php

namespace App\DTOs\Consultations;

use App\Enums\Consultations\ConsultationAllocationScope;
use App\Enums\Consultations\ConsultationType;
use Carbon\CarbonImmutable;

final readonly class ConsultationCoverageSummary
{
    public function __construct(
        public ConsultationType $type,
        public ConsultationAllocationScope $scope,
        public ?int $limit,
        public int $completed,
        public int $reserved,
        public ?CarbonImmutable $resetAt,
        public ?string $unavailableReason = null,
    ) {}

    public function remaining(): ?int
    {
        return $this->limit === null
            ? null
            : max(0, $this->limit - $this->completed - $this->reserved);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'label' => $this->type->label(),
            'scope' => $this->scope->value,
            'scopeLabel' => $this->scope->label(),
            'limit' => $this->limit,
            'completed' => $this->completed,
            'reserved' => $this->reserved,
            'remaining' => $this->remaining(),
            'resetAt' => $this->resetAt?->toISOString(),
            'unavailableReason' => $this->unavailableReason,
        ];
    }
}
