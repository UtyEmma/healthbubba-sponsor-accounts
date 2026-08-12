<?php

namespace App\Http\Resources;

use App\Models\MedicalAccessRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MedicalAccessRequest */
final class MedicalAccessRequestResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'publicId' => $this->public_id,
            'beneficiary' => [
                'name' => trim("{$this->workspaceBeneficiary->first_name} {$this->workspaceBeneficiary->last_name}"),
                'email' => $this->workspaceBeneficiary->email,
            ],
            'workspace' => [
                'name' => $this->workspace->name,
            ],
            'requestedByName' => $this->requester?->name,
            'dataType' => [
                'value' => $this->data_type->value,
                'label' => $this->data_type->label(),
            ],
            'reason' => $this->reason,
            'status' => $this->status->value,
            'requestedAt' => $this->requested_at->toISOString(),
            'reviewExpiresAt' => $this->review_expires_at->toISOString(),
            'approvedAt' => $this->approved_at?->toISOString(),
            'deniedAt' => $this->denied_at?->toISOString(),
            'accessExpiresAt' => $this->access_expires_at?->toISOString(),
        ];
    }
}
