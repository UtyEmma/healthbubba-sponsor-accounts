<?php

namespace App\Http\Resources;

use App\Models\WorkspaceBeneficiary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkspaceBeneficiary */
final class WorkspaceBeneficiaryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'publicId' => $this->public_id,
            'firstName' => $this->first_name,
            'lastName' => $this->last_name,
            'name' => trim("{$this->first_name} {$this->last_name}"),
            'email' => $this->email,
            'phone' => $this->phone,
            'department' => $this->department,
            'employeeId' => $this->employee_id,
            'status' => $this->status->value,
            'source' => $this->source->value,
            'hasHealthBubbaAccount' => $this->beneficiary_id !== null,
            'invitedAt' => $this->invited_at->toISOString(),
            'expiresAt' => $this->expires_at->toISOString(),
            'acceptedAt' => $this->accepted_at?->toISOString(),
            'declinedAt' => $this->declined_at?->toISOString(),
            'cancelledAt' => $this->cancelled_at?->toISOString(),
            'suspendedAt' => $this->suspended_at?->toISOString(),
            'revokedAt' => $this->revoked_at?->toISOString(),
        ];
    }
}
