<?php

namespace App\DTOs\Funding;

use App\Models\InstitutionalFundingProgram;

final readonly class ExtendInstitutionalFundingProgramData
{
    public function __construct(
        public InstitutionalFundingProgram $program,
        public int $months,
    ) {}
}
