<?php

namespace App\DTOs\Funding;

use App\Enums\InstitutionalCoverageExpiry;
use App\Enums\InstitutionalCoverageType;
use App\Enums\InstitutionalPaymentPreference;
use Carbon\CarbonImmutable;

final readonly class ResolvedInstitutionalCoverageRules
{
    public function __construct(
        public InstitutionalCoverageType $coverageType,
        public int $gpLimitPerBeneficiary,
        public int $specialistLimitPerBeneficiary,
        public int $dailyConsultationLimit,
        public InstitutionalCoverageExpiry $expiryCadence,
        public InstitutionalPaymentPreference $paymentPreference,
        public CarbonImmutable $periodStart,
        public CarbonImmutable $periodEnd,
    ) {}
}
