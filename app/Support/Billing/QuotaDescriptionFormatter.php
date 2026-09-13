<?php

namespace App\Support\Billing;

use App\Enums\AccountTypes;
use App\Enums\Subscriptions\Features;
use App\Models\Plan;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Revoltify\Subscriptionify\Models\Feature;
use Revoltify\Subscriptionify\Models\FeaturePlan;

final readonly class QuotaDescriptionFormatter
{
    public function format(Feature $feature, ?FeaturePlan $assignment, Plan $plan): string
    {
        if ($assignment === null) {
            return 'Not Included';
        }

        $quota = $this->formatNumber($assignment->getValue());
        $featureType = Features::tryFrom($feature->slug);

        return match ($featureType) {
            Features::BENEFICIARIES_INCLUDED => $plan->account_type === AccountTypes::INDIVIDUAL
                ? "Sponsor + {$quota} beneficiaries included"
                : "{$quota} included",
            Features::MAXIMUM_BENEFICIARIES => $plan->account_type === AccountTypes::INDIVIDUAL
                ? "Sponsor + up to {$quota} beneficiaries"
                : "Up to {$quota}",
            Features::GP_CONSULTATIONS,
            Features::SPECIALIST_CONSULTATIONS => $this->consultationAllowance(
                quota: $quota,
                assignment: $assignment,
                perEmployee: false,
            ),
            Features::GP_CONSULTATIONS_PER_SEAT,
            Features::SPECIALIST_CONSULTATIONS_PER_SEAT => $plan->account_type === AccountTypes::BUSINESS
                ? $this->consultationAllowance(
                    quota: $quota,
                    assignment: $assignment,
                    perEmployee: true,
                )
                : $this->additionalBeneficiaryAllowance($quota, $assignment),
            default => $this->allowanceWithCadence($quota, $assignment),
        };
    }

    private function additionalBeneficiaryAllowance(string $quota, FeaturePlan $assignment): string
    {
        $cadence = $this->cadence($assignment);

        return $cadence === null
            ? "{$quota} per added beneficiary"
            : "{$quota} per added beneficiary / {$cadence}";
    }

    private function consultationAllowance(
        string $quota,
        FeaturePlan $assignment,
        bool $perEmployee,
    ): string {
        $cadence = $this->cadence($assignment);

        if ($perEmployee) {
            return $cadence === null
                ? "{$quota} per employee"
                : "{$quota} per employee / {$cadence}";
        }

        return $this->allowanceWithCadence($quota, $assignment);
    }

    private function allowanceWithCadence(string $quota, FeaturePlan $assignment): string
    {
        $cadence = $this->cadence($assignment);
        $period = $assignment->getResetPeriod();

        if ($cadence === null || $period === null) {
            return $quota;
        }

        return $period === 1
            ? "{$quota} per {$cadence}"
            : "{$quota} every {$cadence}";
    }

    private function cadence(FeaturePlan $assignment): ?string
    {
        $interval = $assignment->getResetInterval();
        $period = $assignment->getResetPeriod();

        if ($interval === null || $period === null) {
            return null;
        }

        return $period === 1
            ? $interval->value
            : "{$period} ".Str::plural($interval->value, $period);
    }

    private function formatNumber(string $number): string
    {
        $formatted = Number::format((float) $number, maxPrecision: 8);

        return $formatted === false ? $number : $formatted;
    }
}
