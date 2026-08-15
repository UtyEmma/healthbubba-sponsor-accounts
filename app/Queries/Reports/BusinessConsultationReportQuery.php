<?php

namespace App\Queries\Reports;

use App\DTOs\Reports\BusinessConsultationReport;
use App\DTOs\Reports\WorkforceStatus;
use App\Enums\Consultations\ConsultationType;
use App\Enums\WorkspaceBeneficiaries\WorkspaceBeneficiaryStatus;
use App\Models\Workspace;
use App\Models\WorkspaceBeneficiary;
use App\Services\Consultations\ConsultationCoverageService;

final readonly class BusinessConsultationReportQuery
{
    public function __construct(
        private ConsultationCoverageService $coverage,
    ) {}

    public function execute(Workspace $workspace): BusinessConsultationReport
    {
        $counts = $this->workforceCounts($workspace);
        $active = $counts['active'];
        $pending = $counts['pending'];
        $suspended = $counts['suspended'];
        $inactive = $counts['inactive'];
        $currentRoster = $active + $pending + $suspended;
        $activationRate = $currentRoster === 0
            ? 0
            : (int) round(($active / $currentRoster) * 100);
        $coverage = $this->coverage->summary($workspace);
        $gpAllocation = collect($coverage['allocations'])
            ->firstWhere('type', ConsultationType::GeneralPractitioner->value);
        $unavailableReason = is_array($gpAllocation)
            ? data_get($gpAllocation, 'unavailableReason')
            : 'GP consultation coverage is unavailable.';
        $remaining = is_array($gpAllocation)
            ? data_get($gpAllocation, 'remaining')
            : 0;
        $isUnavailable = is_string($unavailableReason) && $unavailableReason !== '';

        return new BusinessConsultationReport(
            activeEmployees: $active,
            gpConsultationsRemaining: is_numeric($remaining) ? (int) $remaining : null,
            gpConsultationsUnlimited: ! $isUnavailable && $remaining === null,
            gpConsultationsUnavailableReason: $isUnavailable ? $unavailableReason : null,
            activationRate: $activationRate,
            workforce: $this->workforceRows($counts),
        );
    }

    /** @return array{active: int, inactive: int, pending: int, suspended: int} */
    private function workforceCounts(Workspace $workspace): array
    {
        $now = now();
        $result = WorkspaceBeneficiary::query()
            ->whereBelongsTo($workspace)
            ->toBase()
            ->selectRaw(
                'COUNT(CASE WHEN status = ? AND beneficiary_id IS NOT NULL THEN 1 END) AS active',
                [WorkspaceBeneficiaryStatus::Active->value],
            )
            ->selectRaw(
                'COUNT(CASE WHEN status = ? AND expires_at > ? AND beneficiary_id IS NOT NULL THEN 1 END) AS pending',
                [WorkspaceBeneficiaryStatus::Pending->value, $now],
            )
            ->selectRaw(
                'COUNT(CASE WHEN status = ? THEN 1 END) AS suspended',
                [WorkspaceBeneficiaryStatus::Suspended->value],
            )
            ->selectRaw(
                'COUNT(CASE WHEN beneficiary_id IS NULL AND (status = ? OR (status = ? AND expires_at > ?)) THEN 1 END) AS inactive',
                [
                    WorkspaceBeneficiaryStatus::Active->value,
                    WorkspaceBeneficiaryStatus::Pending->value,
                    $now,
                ],
            )
            ->first();

        return [
            'active' => (int) $result->active,
            'inactive' => (int) $result->inactive,
            'pending' => (int) $result->pending,
            'suspended' => (int) $result->suspended,
        ];
    }

    /**
     * @param  array{active: int, inactive: int, pending: int, suspended: int}  $counts
     * @return list<WorkforceStatus>
     */
    private function workforceRows(array $counts): array
    {
        $total = array_sum($counts);
        $labels = [
            'active' => 'Active',
            'inactive' => 'Inactive',
            'pending' => 'Pending',
            'suspended' => 'Suspended',
        ];

        return array_map(
            static fn (string $status): WorkforceStatus => new WorkforceStatus(
                status: $status,
                label: $labels[$status],
                count: $counts[$status],
                percentage: $total === 0
                    ? 0.0
                    : round(($counts[$status] / $total) * 100, 2),
            ),
            array_keys($labels),
        );
    }
}
