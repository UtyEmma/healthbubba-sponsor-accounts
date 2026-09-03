<?php

namespace App\Http\Resources\Auth;

use App\DTOs\Auth\AccountSetupAuthentication;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AccountSetupAuthenticationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var AccountSetupAuthentication $authentication */
        $authentication = $this->resource;

        return [
            'canLogin' => $authentication->activeMembership !== null,
            'loginRedirect' => $authentication->activeMembership === null ? null : route('home'),
        ];
    }
}
