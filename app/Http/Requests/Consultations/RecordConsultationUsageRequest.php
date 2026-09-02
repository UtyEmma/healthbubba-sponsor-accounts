<?php

namespace App\Http\Requests\Consultations;

use Illuminate\Foundation\Http\FormRequest;

final class RecordConsultationUsageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'appointment_id' => ['required', 'integer', 'min:1'],
            'sponsor_id' => ['required', 'integer', 'min:1'],
        ];
    }
}
