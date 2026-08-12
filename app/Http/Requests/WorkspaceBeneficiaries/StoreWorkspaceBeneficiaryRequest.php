<?php

namespace App\Http\Requests\WorkspaceBeneficiaries;

use App\DTOs\WorkspaceBeneficiaries\InviteWorkspaceBeneficiaryData;
use App\Enums\AccountTypes;
use Illuminate\Validation\Rule;

final class StoreWorkspaceBeneficiaryRequest extends AuthorizedWorkspaceBeneficiaryRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        $business = $this->workspace()->type === AccountTypes::BUSINESS;

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'phone' => ['required', 'string', 'max:32'],
            'department' => [Rule::requiredIf($business), 'nullable', 'string', 'max:120'],
            'employee_id' => ['nullable', 'string', 'max:32', 'regex:/^[A-Za-z0-9_-]+$/'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'employee_id' => filled($this->input('employee_id'))
                ? mb_strtoupper(trim((string) $this->input('employee_id')))
                : null,
        ]);
    }

    public function invitationData(): InviteWorkspaceBeneficiaryData
    {
        return InviteWorkspaceBeneficiaryData::fromArray($this->validated());
    }
}
