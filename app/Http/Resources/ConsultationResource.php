<?php

namespace App\Http\Resources;

use App\DTOs\Consultations\ConsultationViewData;
use App\Enums\Consultations\ConsultationType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ConsultationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var ConsultationViewData $consultation */
        $consultation = $this->resource;
        $appointment = $consultation->appointment;
        $patient = $appointment->patient;
        $doctor = $appointment->doctor;
        $planName = $consultation->planName ?? 'workspace plan';

        return [
            'id' => (int) $appointment->getKey(),
            'beneficiary' => [
                'id' => $patient?->getKey() === null ? null : (int) $patient->getKey(),
                'name' => trim("{$patient?->first_name} {$patient?->last_name}"),
                'email' => $patient?->email,
                'phone' => $patient?->phone,
            ],
            'consultationType' => [
                'value' => $consultation->type->value,
                'label' => $consultation->type->label(),
            ],
            'doctor' => $doctor === null ? null : [
                'id' => (int) $doctor->getKey(),
                'name' => trim("{$doctor->first_name} {$doctor->last_name}"),
                'providerType' => $doctor->provider_type,
            ],
            'status' => [
                'value' => strtolower($appointment->status->name),
                'label' => $appointment->status->label(),
            ],
            'scheduledAt' => $consultation->scheduledAt?->toISOString(),
            'cost' => [
                'units' => 1,
                'featureSlug' => $consultation->featureSlug,
                'planName' => $consultation->planName,
                'scope' => $consultation->scope->value,
                'label' => sprintf('1 %s from %s', $consultation->type === ConsultationType::GeneralPractitioner ? 'GP consultation' : 'Specialist consultation', $planName),
            ],
            'createdAt' => $appointment->created_at?->toISOString(),
        ];
    }
}
