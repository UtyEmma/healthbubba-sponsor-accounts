<?php

namespace App\DTOs\Consultations;

final readonly class ConsultationAllocationScaling
{
    /**
     * @param  list<ConsultationScalingStep>  $steps
     */
    public function __construct(
        public bool $available,
        public ?string $unavailableReason,
        public string $capacityLabel,
        public string $capacityUnit,
        public string $capacityUnitPlural,
        public ?int $includedCapacity,
        public ?int $currentCapacity,
        public ?int $maximumCapacity,
        public ?int $gpPerCapacity,
        public ?int $specialistPerCapacity,
        public string $description,
        public array $steps,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'available' => $this->available,
            'unavailableReason' => $this->unavailableReason,
            'capacityLabel' => $this->capacityLabel,
            'capacityUnit' => $this->capacityUnit,
            'capacityUnitPlural' => $this->capacityUnitPlural,
            'includedCapacity' => $this->includedCapacity,
            'currentCapacity' => $this->currentCapacity,
            'maximumCapacity' => $this->maximumCapacity,
            'gpPerCapacity' => $this->gpPerCapacity,
            'specialistPerCapacity' => $this->specialistPerCapacity,
            'description' => $this->description,
            'steps' => array_map(
                static fn (ConsultationScalingStep $step): array => $step->toArray(),
                $this->steps,
            ),
        ];
    }
}
