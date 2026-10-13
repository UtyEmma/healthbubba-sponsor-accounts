<?php

namespace App\Actions\Funding;

use App\DTOs\Funding\UpdateInstitutionalCoverageRulesData;
use App\Models\InstitutionalFundingProgram;
use Illuminate\Support\Facades\DB;

final readonly class UpdateInstitutionalCoverageRulesAction
{
    public function execute(UpdateInstitutionalCoverageRulesData $data): InstitutionalFundingProgram
    {
        return DB::transaction(function () use ($data): InstitutionalFundingProgram {
            $program = InstitutionalFundingProgram::query()
                ->whereKey($data->program->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $program->update([
                'coverage_type' => $data->coverageType,
                'scheduled_consultation_limit_per_beneficiary' => $data->scheduledConsultationLimitPerBeneficiary,
                'instant_consultation_limit_per_beneficiary' => $data->instantConsultationLimitPerBeneficiary,
                'daily_consultation_limit' => $data->dailyConsultationLimit,
                'expiry_cadence' => $data->expiryCadence,
                'payment_preference' => $data->paymentPreference,
            ]);

            return $program->refresh();
        }, 3);
    }
}
