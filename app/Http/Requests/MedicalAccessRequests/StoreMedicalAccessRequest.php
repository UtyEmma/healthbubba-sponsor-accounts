<?php

namespace App\Http\Requests\MedicalAccessRequests;

use App\DTOs\MedicalAccess\CreateMedicalAccessRequestData;
use App\Enums\MedicalAccess\MedicalAccessDataType;
use Illuminate\Validation\Rule;

final class StoreMedicalAccessRequest extends AuthorizedMedicalAccessRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'beneficiary_public_id' => ['required', 'string', 'ulid'],
            'data_type' => ['required', Rule::enum(MedicalAccessDataType::class)],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reason' => filled($this->input('reason'))
                ? trim((string) $this->input('reason'))
                : null,
        ]);
    }

    public function requestData(): CreateMedicalAccessRequestData
    {
        return CreateMedicalAccessRequestData::fromArray($this->validated());
    }
}
