<?php

namespace App\DTOs\Campaigns;

final readonly class SaveCampaignPricingTierData
{
    public function __construct(
        public int $minimumBeneficiaries,
        public ?int $maximumBeneficiaries,
        public string $beneficiaryUnitFee,
        public ?string $monthlyServiceFee,
        public string $currency,
        public bool $isActive,
        public int $sortOrder,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            minimumBeneficiaries: (int) $data['minimum_beneficiaries'],
            maximumBeneficiaries: filled($data['maximum_beneficiaries'] ?? null)
                ? (int) $data['maximum_beneficiaries']
                : null,
            beneficiaryUnitFee: (string) $data['beneficiary_unit_fee'],
            monthlyServiceFee: filled($data['monthly_service_fee'] ?? null)
                ? (string) $data['monthly_service_fee']
                : null,
            currency: mb_strtoupper((string) $data['currency']),
            isActive: (bool) $data['is_active'],
            sortOrder: (int) $data['sort_order'],
        );
    }
}
