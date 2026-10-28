<?php

namespace App\DTOs\Campaigns;

use App\Models\CampaignPricingTier;

final readonly class CampaignPricingTierData
{
    public function __construct(
        public int $id,
        public int $minimumBeneficiaries,
        public ?int $maximumBeneficiaries,
        public string $beneficiaryUnitFee,
        public ?string $monthlyServiceFee,
        public string $currency,
    ) {}

    public static function fromModel(CampaignPricingTier $tier): self
    {
        return new self(
            id: (int) $tier->getKey(),
            minimumBeneficiaries: $tier->minimum_beneficiaries,
            maximumBeneficiaries: $tier->maximum_beneficiaries,
            beneficiaryUnitFee: $tier->beneficiary_unit_fee,
            monthlyServiceFee: $tier->monthly_service_fee,
            currency: $tier->currency,
        );
    }
}
