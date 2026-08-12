<?php

namespace App\Http\Controllers\MedicalAccessRequests;

use App\Actions\MedicalAccess\CreateMedicalAccessRequestAction;
use App\Http\Requests\MedicalAccessRequests\StoreMedicalAccessRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

final readonly class StoreMedicalAccessRequestController
{
    public function __construct(private CreateMedicalAccessRequestAction $createRequest) {}

    public function __invoke(StoreMedicalAccessRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $this->createRequest->execute($request->workspace(), $user, $request->requestData());

        return redirect()
            ->route('medical_access.index')
            ->with('success', 'Medical access request sent successfully.');
    }
}
