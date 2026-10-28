<?php

namespace App\Services\Campaigns;

use App\DTOs\Campaigns\CampaignPricingQuoteData;
use App\DTOs\Campaigns\CampaignPricingTierData;
use App\Models\CampaignPricingTier;
use App\ValueObjects\Money;
use Illuminate\Validation\ValidationException;

final readonly class CampaignPricingService
{
    /** @return list<CampaignPricingTierData> */
    public function activeTiers(string $currency): array
    {
        $tiers = CampaignPricingTier::query()
            ->activeForCurrency($currency)
            ->orderBy('minimum_beneficiaries')
            ->orderBy('sort_order')
            ->get()
            ->map(static fn (CampaignPricingTier $tier): CampaignPricingTierData => CampaignPricingTierData::fromModel($tier))
            ->values()
            ->all();

        return array_values($tiers);
    }

    public function quote(
        int $beneficiaries,
        string $currency,
        bool $lockForUpdate = false,
    ): CampaignPricingQuoteData {
        $currency = mb_strtoupper($currency);
        $query = CampaignPricingTier::query()
            ->activeForCurrency($currency)
            ->where('minimum_beneficiaries', '<=', $beneficiaries)
            ->where(function ($query) use ($beneficiaries): void {
                $query->whereNull('maximum_beneficiaries')
                    ->orWhere('maximum_beneficiaries', '>=', $beneficiaries);
            })
            ->orderByDesc('minimum_beneficiaries')
            ->orderBy('sort_order');

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        $tier = $query->first();

        if (! $tier instanceof CampaignPricingTier) {
            throw ValidationException::withMessages([
                'estimated_beneficiaries' => "No active campaign pricing tier covers {$beneficiaries} beneficiaries in {$currency}.",
            ]);
        }

        $unitFee = Money::fromMajor($tier->beneficiary_unit_fee, $tier->currency);
        $setupFee = $unitFee->multiply($beneficiaries);
        $monthlyServiceFee = $tier->monthly_service_fee === null
            ? null
            : Money::fromMajor($tier->monthly_service_fee, $tier->currency)->toMajorAmount();

        return new CampaignPricingQuoteData(
            tierId: (int) $tier->getKey(),
            beneficiaries: $beneficiaries,
            beneficiaryUnitFee: $unitFee->toMajorAmount(),
            beneficiarySetupFee: $setupFee->toMajorAmount(),
            monthlyServiceFee: $monthlyServiceFee,
            currency: $unitFee->currency,
        );
    }
}
