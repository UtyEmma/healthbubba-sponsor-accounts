<?php

namespace App\Http\Resources;

use App\Models\Consultations\Consultation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Consultation */
final class ConsultationUsageResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'recorded' => true,
            'usageReference' => $this->public_id,
            'appointmentId' => $this->appointment_id,
            'patientId' => $this->beneficiary_id,
            'doctorId' => $this->doctor_id,
            'sponsor' => [
                'id' => $this->workspace_id,
                'name' => $this->workspace->name,
                'type' => [
                    'value' => $this->workspace->type->value,
                    'label' => $this->workspace->type->label(),
                ],
            ],
            'consultationType' => [
                'value' => $this->consultation_type->value,
                'label' => $this->consultation_type->label(),
            ],
            'coverageName' => $this->plan_name,
            'recordedAt' => $this->confirmed_at?->toISOString(),
        ];
    }
}
