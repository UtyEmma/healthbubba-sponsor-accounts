<?php

namespace App\Http\Controllers\MedicalAccessRequests;

use App\Actions\MedicalAccess\ExpireMedicalAccessRequestsAction;
use App\Enums\MedicalAccess\MedicalAccessDataType;
use App\Enums\WorkspaceBeneficiaries\WorkspaceBeneficiaryStatus;
use App\Http\Requests\MedicalAccessRequests\IndexMedicalAccessRequest;
use App\Http\Resources\MedicalAccessBeneficiaryResource;
use App\Http\Resources\MedicalAccessRequestResource;
use Inertia\Inertia;
use Inertia\Response;

final readonly class MedicalAccessIndexController
{
    public function __construct(private ExpireMedicalAccessRequestsAction $expireRequests) {}

    public function __invoke(IndexMedicalAccessRequest $request): Response
    {
        $workspace = $request->workspace();
        $this->expireRequests->execute($workspace);

        $medicalAccessRequests = $workspace->medicalAccessRequests()
            ->with([
                'workspace:id,name',
                'workspaceBeneficiary:id,first_name,last_name,email',
                'requester:id,name',
            ])
            ->latest('requested_at')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();
        $beneficiaries = $workspace->workspaceBeneficiaries()
            ->select(['id', 'public_id', 'first_name', 'last_name', 'email'])
            ->where('status', WorkspaceBeneficiaryStatus::Active)
            ->whereNotNull('beneficiary_id')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return Inertia::render('sponsor/medical-access/index', [
            'requests' => MedicalAccessRequestResource::collection($medicalAccessRequests),
            'beneficiaries' => MedicalAccessBeneficiaryResource::collection($beneficiaries)->resolve($request),
            'dataTypes' => MedicalAccessDataType::options(),
        ]);
    }
}
