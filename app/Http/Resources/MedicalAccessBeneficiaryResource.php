<?php

namespace App\Http\Resources;

use App\Models\WorkspaceBeneficiary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkspaceBeneficiary */
final class MedicalAccessBeneficiaryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'publicId' => $this->public_id,
            'name' => trim("{$this->first_name} {$this->last_name}"),
            'email' => $this->email,
        ];
    }
}
