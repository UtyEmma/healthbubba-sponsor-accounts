<?php

namespace App\Http\Controllers\MedicalAccessRequests;

use App\Actions\MedicalAccess\ExpireMedicalAccessRequestsAction;
use App\Http\Resources\MedicalAccessRequestResource;
use App\Models\MedicalAccessRequest;
use App\Services\MedicalAccess\MedicalAccessRequestUrlService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ShowMedicalAccessRequestReviewController
{
    public function __construct(
        private ExpireMedicalAccessRequestsAction $expireRequests,
        private MedicalAccessRequestUrlService $urls,
    ) {}

    public function __invoke(Request $request, MedicalAccessRequest $medicalAccessRequest): Response
    {
        $medicalAccessRequest = $this->expireRequests->executeForRequest($medicalAccessRequest);
        $medicalAccessRequest->loadMissing([
            'workspace:id,name',
            'workspaceBeneficiary:id,first_name,last_name,email',
            'requester:id,name',
        ]);

        return Inertia::render('sponsor/medical-access/review', [
            'accessRequest' => (new MedicalAccessRequestResource($medicalAccessRequest))->resolve($request),
            'decisionUrl' => $medicalAccessRequest->isPending()
                ? $this->urls->decision($medicalAccessRequest)
                : null,
        ]);
    }
}
