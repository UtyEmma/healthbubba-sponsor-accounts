<?php

namespace App\Http\Requests\Funding;

final class ViewInstitutionalFundingRequest extends AuthorizedInstitutionalFundingRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [];
    }
}
