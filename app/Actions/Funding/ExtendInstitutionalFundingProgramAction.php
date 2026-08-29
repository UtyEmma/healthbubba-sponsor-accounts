<?php

namespace App\Actions\Funding;

use App\DTOs\Funding\ExtendInstitutionalFundingProgramData;
use App\Models\InstitutionalFundingProgram;
use Illuminate\Support\Facades\DB;

final readonly class ExtendInstitutionalFundingProgramAction
{
    public function execute(ExtendInstitutionalFundingProgramData $data): InstitutionalFundingProgram
    {
        return DB::transaction(function () use ($data): InstitutionalFundingProgram {
            $program = InstitutionalFundingProgram::query()
                ->whereKey($data->program->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $program->update([
                'ends_on' => $program->ends_on->copy()->addMonthsNoOverflow($data->months),
            ]);

            return $program->refresh();
        }, 3);
    }
}
