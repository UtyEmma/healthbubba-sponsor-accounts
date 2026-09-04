<?php

namespace App\Http\Requests\Consultations;

use App\Models\Consultations\Appointment;
use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreConsultationReservationRequest extends FormRequest
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
            'sponsor_id' => [
                'required',
                'integer',
                'min:1',
                Rule::exists(Workspace::class, 'id'),
            ],
        ];
    }
}
