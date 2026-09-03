<?php

namespace App\DTOs\Institutional;

final readonly class InstitutionalCommunityReportRow
{
    public function __construct(
        public ?string $state,
        public ?string $lga,
        public ?string $ward,
        public string $community,
        public int $beneficiaries,
        public int $consultations,
    ) {}

    /** @return array<string, string|int|null> */
    public function toArray(): array
    {
        return [
            'state' => $this->state,
            'lga' => $this->lga,
            'ward' => $this->ward,
            'community' => $this->community,
            'beneficiaries' => $this->beneficiaries,
            'consultations' => $this->consultations,
        ];
    }
}
