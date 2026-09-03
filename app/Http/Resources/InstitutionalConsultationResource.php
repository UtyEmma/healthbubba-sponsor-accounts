<?php

namespace App\Http\Resources;

use App\DTOs\Institutional\InstitutionalConsultationRow;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class InstitutionalConsultationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return $this->resource instanceof InstitutionalConsultationRow
            ? $this->resource->toArray()
            : [];
    }
}
