<?php

namespace App\Http\Requests\WorkspaceBeneficiaries;

final class ManageWorkspaceBeneficiaryRequest extends AuthorizedWorkspaceBeneficiaryRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }
}
