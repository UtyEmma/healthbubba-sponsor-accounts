<?php

namespace App\Http\Requests\WorkspaceBeneficiaries;

use App\Enums\WorkspaceBeneficiaries\WorkspaceBeneficiaryAccessAction;
use Illuminate\Validation\Rule;

final class UpdateWorkspaceBeneficiaryAccessRequest extends AuthorizedWorkspaceBeneficiaryRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::enum(WorkspaceBeneficiaryAccessAction::class)],
        ];
    }

    public function accessAction(): WorkspaceBeneficiaryAccessAction
    {
        return WorkspaceBeneficiaryAccessAction::from($this->string('action')->toString());
    }
}
