<?php

namespace App\Http\Resources;

use App\DTOs\Reports\BusinessConsultationReport;
use App\DTOs\Reports\WorkforceStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin BusinessConsultationReport */
final class BusinessConsultationReportResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'stats' => [
                'activeEmployees' => $this->activeEmployees,
                'scheduledConsultations' => [
                    'remaining' => $this->scheduledConsultationsRemaining,
                    'unlimited' => $this->scheduledConsultationsUnlimited,
                    'unavailableReason' => $this->scheduledConsultationsUnavailableReason,
                ],
                'activationRate' => $this->activationRate,
            ],
            'workforce' => array_map(
                static fn (WorkforceStatus $status): array => [
                    'status' => $status->status,
                    'label' => $status->label,
                    'count' => $status->count,
                    'percentage' => $status->percentage,
                ],
                $this->workforce,
            ),
        ];
    }
}
