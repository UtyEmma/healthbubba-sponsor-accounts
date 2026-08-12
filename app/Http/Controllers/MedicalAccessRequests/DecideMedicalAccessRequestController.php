<?php

namespace App\Http\Controllers\MedicalAccessRequests;

use App\Actions\MedicalAccess\DecideMedicalAccessRequestAction;
use App\Http\Requests\MedicalAccessRequests\DecideMedicalAccessRequest;
use App\Models\MedicalAccessRequest;
use App\Services\MedicalAccess\MedicalAccessRequestUrlService;
use Illuminate\Http\RedirectResponse;

final readonly class DecideMedicalAccessRequestController
{
    public function __construct(
        private DecideMedicalAccessRequestAction $decideRequest,
        private MedicalAccessRequestUrlService $urls,
    ) {}

    public function __invoke(
        DecideMedicalAccessRequest $request,
        MedicalAccessRequest $medicalAccessRequest,
    ): RedirectResponse {
        $medicalAccessRequest = $this->decideRequest->execute(
            $medicalAccessRequest,
            $request->string('decision')->toString(),
        );

        return redirect()->to($this->urls->review($medicalAccessRequest));
    }
}
