<?php

namespace App\Http\Controllers\Api\Consultations;

use App\Http\Requests\Consultations\StoreConsultationEligibilityRequest;
use App\Http\Resources\PatientConsultationSponsorshipResource;
use App\Models\Beneficiary;
use App\Queries\Consultations\PatientConsultationSponsorshipQuery;

final readonly class CheckConsultationEligibilityController
{
    public function __construct(private PatientConsultationSponsorshipQuery $sponsorships) {}

    public function __invoke(StoreConsultationEligibilityRequest $request): PatientConsultationSponsorshipResource
    {
        $patient = Beneficiary::query()->findOrFail($request->patientId());

        return new PatientConsultationSponsorshipResource(
            $this->sponsorships->getForPatient($patient),
        );
    }
}
