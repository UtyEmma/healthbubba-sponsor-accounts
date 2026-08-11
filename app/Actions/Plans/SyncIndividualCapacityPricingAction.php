<?php

namespace App\Actions\Plans;

use App\Enums\AccountTypes;
use App\Enums\Subscriptions\Features;
use App\Models\Plan;
use App\ValueObjects\Money;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use Revoltify\Subscriptionify\Models\Feature;

final class SyncIndividualCapacityPricingAction
{
    public function execute(
        Plan $plan,
        int $includedCapacity,
        int $maximumCapacity,
        string $unitPrice,
    ): void {
        if ($plan->account_type !== AccountTypes::INDIVIDUAL) {
            throw new InvalidArgumentException('Beneficiary capacity pricing can only be configured for individual plans.');
        }

        if ($includedCapacity < 1 || $maximumCapacity < $includedCapacity) {
            throw new InvalidArgumentException('The beneficiary capacity configuration is invalid.');
        }

        $normalizedUnitPrice = Money::fromMajor(
            $unitPrice,
            config()->string('payments.currency', 'NGN'),
        )->toMajorAmount();

        DB::transaction(function () use ($plan, $includedCapacity, $maximumCapacity, $normalizedUnitPrice): void {
            $features = Feature::query()
                ->whereIn('slug', [
                    Features::BENEFICIARIES_INCLUDED->value,
                    Features::MAXIMUM_BENEFICIARIES->value,
                ])
                ->get()
                ->keyBy('slug');

            $includedFeature = $features->get(Features::BENEFICIARIES_INCLUDED->value);
            $maximumFeature = $features->get(Features::MAXIMUM_BENEFICIARIES->value);

            if (! $includedFeature instanceof Feature || ! $maximumFeature instanceof Feature) {
                throw new LogicException('The beneficiary capacity features are missing from the feature catalog.');
            }

            $plan->features()->syncWithoutDetaching([
                $includedFeature->getKey() => [
                    'value' => (string) $includedCapacity,
                    'unit_price' => $normalizedUnitPrice,
                    'reset_period' => null,
                    'reset_interval' => null,
                ],
                $maximumFeature->getKey() => [
                    'value' => (string) $maximumCapacity,
                    'unit_price' => '0',
                    'reset_period' => null,
                    'reset_interval' => null,
                ],
            ]);

            $plan->unsetRelation('features');
        }, 3);
    }
}
