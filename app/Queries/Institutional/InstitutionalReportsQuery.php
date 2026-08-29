<?php

namespace App\Queries\Institutional;

use App\DTOs\Institutional\InstitutionalCommunityReportRow;
use App\Enums\CampaignUsageBenefit;
use App\Enums\Consultations\ConsultationReservationStatus;
use App\Enums\InstitutionalReportType;
use App\Enums\WorkspaceBeneficiaries\WorkspaceBeneficiaryStatus;
use App\Models\Campaign;
use App\Models\CampaignUsageEntry;
use App\Models\Consultations\Consultation;
use App\Models\Workspace;
use App\Models\WorkspaceBeneficiary;
use App\Queries\InstitutionalCampaigns\CampaignMetricsQuery;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

final readonly class InstitutionalReportsQuery
{
    public function __construct(private CampaignMetricsQuery $metrics) {}

    /** @return array<string, mixed> */
    public function get(Workspace $workspace): array
    {
        $campaigns = Campaign::query()
            ->whereBelongsTo($workspace)
            ->withCount('beneficiaries')
            ->orderByDesc('start_date')
            ->get();
        $this->metrics->hydrate($campaigns);
        $byCampaign = $campaigns->map(fn (Campaign $campaign): array => $this->campaignRow($campaign))->values();
        $community = $this->communityRows($workspace, $campaigns)
            ->map(fn (InstitutionalCommunityReportRow $row): array => $row->toArray());
        $allocated = $byCampaign->sum(fn (array $row): float => (float) $row['allocated']);
        $utilized = $byCampaign->sum(fn (array $row): float => (float) $row['utilized']);
        $consultations = $byCampaign->sum(fn (array $row): int => (int) $row['gpUsed'] + (int) $row['specialistUsed']);
        $reservationCounts = Consultation::query()
            ->whereBelongsTo($workspace)
            ->toBase()
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('COUNT(CASE WHEN status = ? THEN 1 END) AS confirmed', [ConsultationReservationStatus::Confirmed->value])
            ->first();
        $totalReservations = (int) ($reservationCounts->total ?? 0);
        $confirmedReservations = (int) ($reservationCounts->confirmed ?? 0);

        return [
            'byCampaign' => $byCampaign,
            'community' => $community,
            'impact' => [
                'reach' => WorkspaceBeneficiary::query()->whereBelongsTo($workspace)
                    ->where('relatable_type', (new Campaign)->getMorphClass())
                    ->whereIn('status', [WorkspaceBeneficiaryStatus::Active, WorkspaceBeneficiaryStatus::Suspended])
                    ->distinct('email')
                    ->count('email'),
                'utilizationPercentage' => $allocated <= 0 ? 0 : (int) round(($utilized / $allocated) * 100),
                'fundsDeployed' => number_format($utilized, 2, '.', ''),
                'consultationsEnabled' => $consultations,
                'completionPercentage' => $totalReservations === 0 ? 0 : (int) round(($confirmedReservations / $totalReservations) * 100),
                'averageConsultationCost' => $consultations === 0 ? '0.00' : number_format($this->consultationUtilized($byCampaign) / $consultations, 2, '.', ''),
            ],
        ];
    }

    /** @return array{title: string, headers: list<string>, rows: array<int, array<int, string|int|float|null>>} */
    public function export(Workspace $workspace, InstitutionalReportType $type): array
    {
        return match ($type) {
            InstitutionalReportType::Beneficiaries => $this->beneficiaryExport($workspace),
            InstitutionalReportType::Coverage => $this->coverageExport($workspace),
            InstitutionalReportType::Utilization => $this->utilizationExport($workspace),
        };
    }

    /** @return array<string, mixed> */
    private function campaignRow(Campaign $campaign): array
    {
        $financial = $campaign->getAttribute('financial_metrics');
        $ended = $campaign->lifecycleStatus()->value === 'COMPLETED';

        return [
            'name' => $campaign->name,
            'slug' => $campaign->slug,
            'location' => $campaign->location,
            'status' => $campaign->lifecycleStatus()->value,
            'statusLabel' => $campaign->lifecycleStatus()->label(),
            'gpUsed' => (int) data_get($financial, 'consultations.gp.confirmed', 0),
            'gpAllocated' => (int) data_get($financial, 'consultations.gp.units', 0),
            'specialistUsed' => (int) data_get($financial, 'consultations.specialist.confirmed', 0),
            'specialistAllocated' => (int) data_get($financial, 'consultations.specialist.units', 0),
            'specialistUnitFee' => (string) data_get($financial, 'consultations.specialist.unitFee', '0.00'),
            'gpUnitFee' => (string) data_get($financial, 'consultations.gp.unitFee', '0.00'),
            'medicationUsed' => (string) data_get($financial, 'budgets.medication.used', '0.00'),
            'medicationAllocated' => (string) data_get($financial, 'budgets.medication.allocated', '0.00'),
            'laboratoryUsed' => (string) data_get($financial, 'budgets.laboratory.used', '0.00'),
            'allocated' => (string) data_get($financial, 'allocated', '0.00'),
            'utilized' => (string) data_get($financial, 'utilized', '0.00'),
            'remaining' => $ended ? null : (string) data_get($financial, 'reserved', '0.00'),
            'returned' => $ended ? (string) data_get($financial, 'returned', '0.00') : null,
            'people' => (int) $campaign->getAttribute('beneficiaries_count'),
            'utilizationPercentage' => (int) data_get($financial, 'utilizationPercentage', 0),
        ];
    }

    /**
     * @param  EloquentCollection<int, Campaign>  $campaigns
     * @return Collection<int, InstitutionalCommunityReportRow>
     */
    private function communityRows(Workspace $workspace, EloquentCollection $campaigns): Collection
    {
        $beneficiaryTable = (new WorkspaceBeneficiary)->getTable();
        $beneficiaries = WorkspaceBeneficiary::query()
            ->whereBelongsTo($workspace)
            ->where('relatable_type', (new Campaign)->getMorphClass())
            ->selectRaw("workspace_beneficiaries.relatable_id AS campaign_id, COALESCE(NULLIF(workspace_beneficiaries.community, ''), 'Unspecified') AS community, COUNT(*) AS beneficiaries")
            ->groupBy('workspace_beneficiaries.relatable_id')
            ->groupByRaw("COALESCE(NULLIF(workspace_beneficiaries.community, ''), 'Unspecified')")
            ->get();
        $usages = CampaignUsageEntry::query()
            ->whereBelongsTo($workspace)
            ->join($beneficiaryTable, 'campaign_usage_entries.workspace_beneficiary_id', '=', 'workspace_beneficiaries.id')
            ->whereIn('campaign_usage_entries.benefit', [
                CampaignUsageBenefit::GeneralPractitioner->value,
                CampaignUsageBenefit::Specialist->value,
            ])
            ->selectRaw("campaign_usage_entries.campaign_id, COALESCE(NULLIF(workspace_beneficiaries.community, ''), 'Unspecified') AS community, SUM(COALESCE(campaign_usage_entries.quantity, 0)) AS consultations")
            ->groupBy('campaign_usage_entries.campaign_id')
            ->groupByRaw("COALESCE(NULLIF(workspace_beneficiaries.community, ''), 'Unspecified')")
            ->get()
            ->keyBy(fn (CampaignUsageEntry $row): string => data_get($row, 'campaign_id').'|'.data_get($row, 'community'));
        $campaignMap = $campaigns->keyBy('id');

        return $beneficiaries->map(function (WorkspaceBeneficiary $row) use ($campaignMap, $usages): InstitutionalCommunityReportRow {
            $campaignId = (int) data_get($row, 'campaign_id');
            $community = (string) data_get($row, 'community');
            $campaign = $campaignMap->get($campaignId);
            $usage = $usages->get($campaignId.'|'.$community);

            return new InstitutionalCommunityReportRow(
                state: $campaign?->state,
                lga: $campaign?->city,
                ward: null,
                community: $community,
                beneficiaries: (int) data_get($row, 'beneficiaries', 0),
                consultations: (int) data_get($usage, 'consultations', 0),
            );
        })->sortByDesc('consultations')->values();
    }

    /** @param Collection<int, array<string, mixed>> $rows */
    private function consultationUtilized(Collection $rows): float
    {
        return $rows->sum(static fn (array $row): float => ((int) $row['gpUsed'] * (float) $row['gpUnitFee'])
            + ((int) $row['specialistUsed'] * (float) $row['specialistUnitFee'])
        );
    }

    /** @return array{title: string, headers: list<string>, rows: array<int, array<int, string|int|float|null>>} */
    private function beneficiaryExport(Workspace $workspace): array
    {
        $rows = WorkspaceBeneficiary::query()
            ->whereBelongsTo($workspace)
            ->where('relatable_type', (new Campaign)->getMorphClass())
            ->with('relatable')
            ->orderBy('last_name')->orderBy('first_name')->get()
            ->map(fn (WorkspaceBeneficiary $row): array => [
                trim("{$row->first_name} {$row->last_name}"), $row->email, $row->phone,
                $row->relatable instanceof Campaign ? $row->relatable->name : null,
                $row->community, $row->status->value, $row->source->value,
            ])->values()->all();

        return ['title' => 'Beneficiary Report', 'headers' => ['Beneficiary', 'Email', 'Phone', 'Campaign', 'Community', 'Status', 'Source'], 'rows' => $rows];
    }

    /** @return array{title: string, headers: list<string>, rows: array<int, array<int, string|int|float|null>>} */
    private function coverageExport(Workspace $workspace): array
    {
        $report = $this->get($workspace);
        $rows = $report['byCampaign']->map(fn (array $row): array => [$row['name'], $row['statusLabel'], $row['allocated'], $row['utilized'], $row['remaining'], $row['returned'], $row['people']])->values()->all();

        return ['title' => 'Coverage Report', 'headers' => ['Campaign', 'Status', 'Allocated', 'Utilized', 'Remaining', 'Returned', 'People'], 'rows' => $rows];
    }

    /** @return array{title: string, headers: list<string>, rows: array<int, array<int, string|int|float|null>>} */
    private function utilizationExport(Workspace $workspace): array
    {
        $rows = CampaignUsageEntry::query()->whereBelongsTo($workspace)
            ->with(['campaign', 'workspaceBeneficiary'])->latest('occurred_at')->get()
            ->map(fn (CampaignUsageEntry $entry): array => [
                $entry->occurred_at->toDateString(), $entry->campaign->name, $entry->benefit->label(),
                $entry->workspaceBeneficiary === null ? null : trim($entry->workspaceBeneficiary->first_name.' '.$entry->workspaceBeneficiary->last_name),
                $entry->quantity, $entry->total_amount, $entry->source->value,
            ])->values()->all();

        return ['title' => 'Utilization Report', 'headers' => ['Date', 'Campaign', 'Benefit', 'Beneficiary', 'Quantity', 'Amount', 'Source'], 'rows' => $rows];
    }
}
