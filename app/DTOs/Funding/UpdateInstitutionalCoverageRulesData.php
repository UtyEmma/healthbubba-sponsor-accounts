<?php

namespace App\DTOs\Funding;

use App\Enums\InstitutionalCoverageExpiry;
use App\Enums\InstitutionalCoverageType;
use App\Enums\InstitutionalPaymentPreference;
use App\Models\InstitutionalFundingProgram;

final readonly class UpdateInstitutionalCoverageRulesData
{
    public function __construct(
        public InstitutionalFundingProgram $program,
        public InstitutionalCoverageType $coverageType,
        public int $gpLimitPerBeneficiary,
        public int $specialistLimitPerBeneficiary,
        public int $dailyConsultationLimit,
        public InstitutionalCoverageExpiry $expiryCadence,
        public InstitutionalPaymentPreference $paymentPreference,
    ) {}
}
