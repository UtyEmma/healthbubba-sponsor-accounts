<?php

namespace App\Http\Requests\Consultations;

use App\Models\Consultations\Appointment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RecordConsultationUsageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'appointment_id' => [
                'required',
                'integer',
                'min:1',
                Rule::exists(Appointment::class, 'appointment_id'),
            ],
        ];
    }
}
