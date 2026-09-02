<?php

namespace App\Http\Controllers\Api\Consultations;

use App\Actions\Consultations\RecordConsultationUsageAction;
use App\DTOs\Consultations\RecordConsultationUsageData;
use App\Http\Requests\Consultations\RecordConsultationUsageRequest;
use App\Http\Resources\ConsultationUsageResource;

final readonly class RecordConsultationUsageController
{
    public function __construct(private RecordConsultationUsageAction $recordUsage) {}

    public function __invoke(RecordConsultationUsageRequest $request): ConsultationUsageResource
    {
        return new ConsultationUsageResource(
            $this->recordUsage->execute(
                RecordConsultationUsageData::fromRequest($request),
            ),
        );
    }
}
