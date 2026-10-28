<?php

namespace App\Actions\Campaigns;

use App\DTOs\Campaigns\SaveCampaignPricingTierData;
use App\Models\CampaignPricingTier;
use App\Models\User;
use App\ValueObjects\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class SaveCampaignPricingTierAction
{
    public function execute(
        SaveCampaignPricingTierData $data,
        User $administrator,
        ?CampaignPricingTier $pricingTier = null,
    ): CampaignPricingTier {
        abort_unless($administrator->isAdmin(), 403);

        return DB::transaction(function () use ($data, $administrator, $pricingTier): CampaignPricingTier {
            $tiers = CampaignPricingTier::query()->lockForUpdate()->get();

            if ($data->maximumBeneficiaries !== null
                && $data->maximumBeneficiaries < $data->minimumBeneficiaries) {
                throw ValidationException::withMessages([
                    'maximum_beneficiaries' => 'The maximum must be greater than or equal to the minimum.',
                ]);
            }

            Money::fromMajor($data->beneficiaryUnitFee, $data->currency);

            if ($data->monthlyServiceFee !== null) {
                Money::fromMajor($data->monthlyServiceFee, $data->currency);
            }

            $overlaps = $data->isActive && $tiers
                ->where('currency', $data->currency)
                ->where('is_active', true)
                ->reject(fn (CampaignPricingTier $tier): bool => $pricingTier?->is($tier) === true)
                ->contains(fn (CampaignPricingTier $tier): bool => $this->rangesOverlap(
                    minimum: $data->minimumBeneficiaries,
                    maximum: $data->maximumBeneficiaries,
                    otherMinimum: $tier->minimum_beneficiaries,
                    otherMaximum: $tier->maximum_beneficiaries,
                ));

            if ($overlaps) {
                throw ValidationException::withMessages([
                    'minimum_beneficiaries' => 'This beneficiary range overlaps another active pricing tier for the same currency.',
                ]);
            }

            $pricingTier ??= new CampaignPricingTier;
            $pricingTier->fill([
                'minimum_beneficiaries' => $data->minimumBeneficiaries,
                'maximum_beneficiaries' => $data->maximumBeneficiaries,
                'beneficiary_unit_fee' => Money::fromMajor($data->beneficiaryUnitFee, $data->currency)->toMajorAmount(),
                'monthly_service_fee' => $data->monthlyServiceFee === null
                    ? null
                    : Money::fromMajor($data->monthlyServiceFee, $data->currency)->toMajorAmount(),
                'currency' => $data->currency,
                'is_active' => $data->isActive,
                'sort_order' => $data->sortOrder,
                'updated_by_user_id' => $administrator->getKey(),
            ]);
            $pricingTier->save();

            return $pricingTier->refresh();
        }, 3);
    }

    private function rangesOverlap(
        int $minimum,
        ?int $maximum,
        int $otherMinimum,
        ?int $otherMaximum,
    ): bool {
        $maximum ??= PHP_INT_MAX;
        $otherMaximum ??= PHP_INT_MAX;

        return $minimum <= $otherMaximum && $otherMinimum <= $maximum;
    }
}
