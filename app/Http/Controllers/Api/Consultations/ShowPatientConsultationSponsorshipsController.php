<?php

namespace App\Http\Controllers\Api\Consultations;

use App\Http\Requests\Consultations\ShowPatientConsultationSponsorshipsRequest;
use App\Http\Resources\PatientConsultationSponsorshipResource;
use App\Models\Beneficiary;
use App\Queries\Consultations\PatientConsultationSponsorshipQuery;

final readonly class ShowPatientConsultationSponsorshipsController
{
    public function __construct(private PatientConsultationSponsorshipQuery $sponsorships) {}

    public function __invoke(
        ShowPatientConsultationSponsorshipsRequest $request,
        Beneficiary $patient,
    ): PatientConsultationSponsorshipResource {
        return new PatientConsultationSponsorshipResource(
            $this->sponsorships->getForPatient($patient),
        );
    }
}
