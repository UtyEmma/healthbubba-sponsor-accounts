<?php

namespace App\Http\Resources\Auth;

use App\DTOs\Auth\AccountAvailability;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AccountAvailabilityResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var AccountAvailability $availability */
        $availability = $this->resource;

        return [
            'status' => $availability->status->value,
            'canLogin' => $availability->canLogin,
            'canSetup' => $availability->canSetup,
        ];
    }
}
