<?php

namespace App\Http\Requests\Consultations;

use App\Enums\BeneficiaryRoles;
use App\Models\Beneficiary;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreConsultationEligibilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'patient_id' => [
                'required',
                'integer',
                'min:1',
                Rule::exists(Beneficiary::class, 'id')
                    ->where('type', BeneficiaryRoles::PATIENT->value),
            ],
        ];
    }

    public function patientId(): int
    {
        return (int) $this->validated('patient_id');
    }
}
