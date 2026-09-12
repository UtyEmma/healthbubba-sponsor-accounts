<?php

namespace App\Http\Resources;

use App\Models\Campaign;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceBeneficiary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkspaceBeneficiary */
final class WorkspaceBeneficiaryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $primarySponsor = $this->relationLoaded('primarySponsor')
            ? $this->getRelation('primarySponsor')
            : null;
        $primarySponsor = $primarySponsor instanceof User ? $primarySponsor : null;
        $name = $primarySponsor instanceof User
            ? $primarySponsor->name
            : trim("{$this->first_name} {$this->last_name}");
        [$firstName, $lastName] = $this->splitName($name);

        return [
            'id' => (int) $this->getKey(),
            'publicId' => $this->public_id,
            'relatable' => $this->relationLoaded('relatable')
                ? $this->relatableData()
                : null,
            'firstName' => $primarySponsor === null ? $this->first_name : $firstName,
            'lastName' => $primarySponsor === null ? $this->last_name : $lastName,
            'name' => $name,
            'email' => $primarySponsor instanceof User ? $primarySponsor->email : $this->email,
            'phone' => $primarySponsor instanceof User ? ($primarySponsor->phone ?? '') : $this->phone,
            'community' => $this->community,
            'department' => $this->department,
            'employeeId' => $this->employee_id,
            'status' => $this->status->value,
            'source' => $this->source->value,
            'hasHealthBubbaAccount' => $this->beneficiary_id !== null,
            'isPrimarySponsor' => $this->isPrimarySponsor(),
            'invitedAt' => $this->invited_at->toISOString(),
            'expiresAt' => $this->expires_at->toISOString(),
            'acceptedAt' => $this->accepted_at?->toISOString(),
            'declinedAt' => $this->declined_at?->toISOString(),
            'cancelledAt' => $this->cancelled_at?->toISOString(),
            'suspendedAt' => $this->suspended_at?->toISOString(),
            'revokedAt' => $this->revoked_at?->toISOString(),
        ];
    }

    /** @return array{string, string} */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2) ?: [];

        return [$parts[0] ?? '', $parts[1] ?? ''];
    }

    /** @return array<string, int|string|null>|null */
    private function relatableData(): ?array
    {
        $relatable = $this->getRelation('relatable');

        if ($relatable instanceof Campaign) {
            return [
                'type' => 'campaign',
                'id' => (int) $relatable->getKey(),
                'name' => $relatable->name,
                'slug' => $relatable->slug,
            ];
        }

        if ($relatable instanceof Workspace) {
            return [
                'type' => 'workspace',
                'id' => (int) $relatable->getKey(),
                'name' => $relatable->name,
                'slug' => null,
            ];
        }

        return null;
    }
}
