<?php

namespace App\Services\Funding;

use App\DTOs\Funding\ResolvedInstitutionalCoverageRules;
use App\Enums\Consultations\ConsultationType;
use App\Models\Campaign;
use App\Models\InstitutionalFundingProgram;
use Carbon\CarbonImmutable;

final readonly class InstitutionalCoverageRulesResolver
{
    public function resolve(
        Campaign $campaign,
        InstitutionalFundingProgram $program,
        ?CarbonImmutable $at = null,
    ): ResolvedInstitutionalCoverageRules {
        $at ??= CarbonImmutable::now();
        [$periodStart, $periodEnd] = $this->annualPeriod($program, $at);

        return new ResolvedInstitutionalCoverageRules(
            coverageType: $campaign->coverage_type_override ?? $program->coverage_type,
            scheduledConsultationLimitPerBeneficiary: $campaign->scheduled_consultation_limit_per_beneficiary_override ?? $program->scheduled_consultation_limit_per_beneficiary,
            instantConsultationLimitPerBeneficiary: $campaign->instant_consultation_limit_per_beneficiary_override ?? $program->instant_consultation_limit_per_beneficiary,
            dailyConsultationLimit: $campaign->daily_consultation_limit_override ?? $program->daily_consultation_limit,
            expiryCadence: $campaign->coverage_expiry_override ?? $program->expiry_cadence,
            paymentPreference: $campaign->payment_preference_override ?? $program->payment_preference,
            periodStart: $periodStart,
            periodEnd: $periodEnd,
        );
    }

    public function beneficiaryLimit(
        ResolvedInstitutionalCoverageRules $rules,
        ConsultationType $type,
    ): int {
        return $type === ConsultationType::Scheduled
            ? $rules->scheduledConsultationLimitPerBeneficiary
            : $rules->instantConsultationLimitPerBeneficiary;
    }

    /** @return array{CarbonImmutable, CarbonImmutable} */
    private function annualPeriod(
        InstitutionalFundingProgram $program,
        CarbonImmutable $at,
    ): array {
        $anchor = $program->starts_on->toImmutable()->startOfDay();
        $years = max(0, $anchor->diffInYears($at));
        $start = $anchor->addYears($years);

        if ($start->isAfter($at)) {
            $start = $anchor->addYears(max(0, $years - 1));
        }

        return [$start, $start->addYear()->subSecond()];
    }
}
