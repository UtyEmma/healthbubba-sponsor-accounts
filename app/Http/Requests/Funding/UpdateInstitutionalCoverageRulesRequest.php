<?php

namespace App\Http\Requests\Funding;

use App\DTOs\Funding\UpdateInstitutionalCoverageRulesData;
use App\Enums\InstitutionalCoverageExpiry;
use App\Enums\InstitutionalCoverageType;
use App\Enums\InstitutionalPaymentPreference;
use Illuminate\Validation\Rule;

final class UpdateInstitutionalCoverageRulesRequest extends AuthorizedInstitutionalFundingRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'coverage_type' => ['required', Rule::enum(InstitutionalCoverageType::class)],
            'gp_limit_per_beneficiary' => ['required', 'integer', 'min:1', 'max:1000'],
            'specialist_limit_per_beneficiary' => ['required', 'integer', 'min:1', 'max:1000'],
            'daily_consultation_limit' => ['required', 'integer', 'min:1', 'max:100'],
            'expiry_cadence' => ['required', Rule::enum(InstitutionalCoverageExpiry::class)],
            'payment_preference' => ['required', Rule::enum(InstitutionalPaymentPreference::class)],
        ];
    }

    public function toData(): UpdateInstitutionalCoverageRulesData
    {
        $program = $this->workspace()->fundingProgram()->firstOrFail();

        return new UpdateInstitutionalCoverageRulesData(
            program: $program,
            coverageType: InstitutionalCoverageType::from((string) $this->validated('coverage_type')),
            gpLimitPerBeneficiary: (int) $this->validated('gp_limit_per_beneficiary'),
            specialistLimitPerBeneficiary: (int) $this->validated('specialist_limit_per_beneficiary'),
            dailyConsultationLimit: (int) $this->validated('daily_consultation_limit'),
            expiryCadence: InstitutionalCoverageExpiry::from((string) $this->validated('expiry_cadence')),
            paymentPreference: InstitutionalPaymentPreference::from((string) $this->validated('payment_preference')),
        );
    }
}
