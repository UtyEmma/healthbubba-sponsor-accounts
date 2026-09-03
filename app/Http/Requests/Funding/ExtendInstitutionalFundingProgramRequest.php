<?php

namespace App\Http\Requests\Funding;

use App\DTOs\Funding\ExtendInstitutionalFundingProgramData;

final class ExtendInstitutionalFundingProgramRequest extends AuthorizedInstitutionalFundingRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return ['months' => ['required', 'integer', 'min:1', 'max:60']];
    }

    public function toData(): ExtendInstitutionalFundingProgramData
    {
        return new ExtendInstitutionalFundingProgramData(
            program: $this->workspace()->fundingProgram()->firstOrFail(),
            months: (int) $this->validated('months'),
        );
    }
}
