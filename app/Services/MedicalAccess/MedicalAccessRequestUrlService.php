<?php

namespace App\Services\MedicalAccess;

use App\Models\MedicalAccessRequest;
use Illuminate\Support\Facades\URL;

final class MedicalAccessRequestUrlService
{
    public function review(MedicalAccessRequest $medicalAccessRequest): string
    {
        return URL::temporarySignedRoute(
            'medical-access-reviews.show',
            $medicalAccessRequest->review_expires_at,
            ['medicalAccessRequest' => $medicalAccessRequest->public_id],
        );
    }

    public function decision(MedicalAccessRequest $medicalAccessRequest): string
    {
        return URL::temporarySignedRoute(
            'medical-access-reviews.decide',
            $medicalAccessRequest->review_expires_at,
            ['medicalAccessRequest' => $medicalAccessRequest->public_id],
        );
    }
}
