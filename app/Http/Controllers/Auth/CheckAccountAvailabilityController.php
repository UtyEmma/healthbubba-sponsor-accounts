<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\CheckAccountAvailabilityAction;
use App\Http\Requests\Auth\CheckAccountAvailabilityRequest;
use App\Http\Resources\Auth\AccountAvailabilityResource;

final readonly class CheckAccountAvailabilityController
{
    public function __construct(private CheckAccountAvailabilityAction $checkAvailability) {}

    public function __invoke(CheckAccountAvailabilityRequest $request): AccountAvailabilityResource
    {
        return new AccountAvailabilityResource($this->checkAvailability->execute(
            $request->email(),
            $request->accountType(),
        ));
    }
}
