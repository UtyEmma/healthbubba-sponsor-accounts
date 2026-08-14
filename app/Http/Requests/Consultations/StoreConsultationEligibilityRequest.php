<?php

namespace App\Http\Requests\Consultations;

use Illuminate\Foundation\Http\FormRequest;

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
            'sponsor_id' => ['required', 'integer', 'min:1'],
            'patient_id' => ['required', 'integer', 'min:1'],
            'doctor_id' => ['required', 'integer', 'min:1'],
        ];
    }
}
