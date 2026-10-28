<?php

namespace Database\Factories;

use App\Models\CampaignPricingTier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CampaignPricingTier>
 */
final class CampaignPricingTierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'minimum_beneficiaries' => 1,
            'maximum_beneficiaries' => 100,
            'beneficiary_unit_fee' => '1500.00',
            'monthly_service_fee' => '100000.00',
            'currency' => 'NGN',
            'is_active' => true,
            'sort_order' => 1,
        ];
    }

    public function openEnded(): static
    {
        return $this->state(fn (): array => [
            'minimum_beneficiaries' => 101,
            'maximum_beneficiaries' => null,
            'beneficiary_unit_fee' => '1000.00',
            'sort_order' => 2,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    public function withoutMonthlyServiceFee(): static
    {
        return $this->state(fn (): array => ['monthly_service_fee' => null]);
    }
}
