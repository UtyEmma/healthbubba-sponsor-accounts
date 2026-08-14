<?php

namespace App\Http\Resources;

use App\Models\Consultations\Consultation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Consultation */
final class ConsultationReservationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'token' => $this->public_id,
            'status' => $this->status->value,
            'appointmentId' => $this->appointment_id,
            'sponsorId' => $this->workspace_id,
            'patientId' => $this->beneficiary_id,
            'doctorId' => $this->doctor_id,
            'consultationType' => [
                'value' => $this->consultation_type->value,
                'label' => $this->consultation_type->label(),
            ],
            'featureSlug' => $this->feature_slug,
            'planName' => $this->plan_name,
            'scope' => $this->allocation_scope->value,
            'reservedAt' => $this->reserved_at->toISOString(),
            'confirmedAt' => $this->confirmed_at?->toISOString(),
            'cancelledAt' => $this->cancelled_at?->toISOString(),
            'heldUntilCancelled' => $this->status->value === 'reserved',
        ];
    }
}
