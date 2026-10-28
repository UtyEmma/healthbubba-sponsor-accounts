<?php

namespace App\Queries\InstitutionalCampaigns;

use App\Enums\CampaignBudgetCategory;
use App\Enums\Consultations\ConsultationReservationStatus;
use App\Enums\Consultations\ConsultationType;
use App\Models\Campaign;
use App\Models\CampaignConsultationQuota;
use App\Models\CampaignUsageEntry;
use App\Models\Consultations\Consultation;
use App\ValueObjects\Money;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

final class CampaignMetricsQuery
{
    /** @param EloquentCollection<int, Campaign> $campaigns */
    public function hydrate(EloquentCollection $campaigns): void
    {
        $campaignIds = $campaigns->modelKeys();

        if ($campaignIds === []) {
            return;
        }

        $quotas = CampaignConsultationQuota::query()
            ->whereIn('campaign_id', $campaignIds)
            ->selectRaw('campaign_id, consultation_type, SUM(quantity) AS units, MAX(unit_fee) AS unit_fee, SUM(total_cost) AS total_cost')
            ->groupBy('campaign_id', 'consultation_type')
            ->get()
            ->groupBy('campaign_id');
        $usages = CampaignUsageEntry::query()
            ->whereIn('campaign_id', $campaignIds)
            ->selectRaw('campaign_id, benefit, SUM(COALESCE(quantity, 0)) AS units_used, SUM(total_amount) AS used')
            ->groupBy('campaign_id', 'benefit')
            ->get()
            ->groupBy('campaign_id');
        $consultations = Consultation::query()
            ->join('workspace_beneficiaries', 'workspace_beneficiaries.id', '=', 'consultations.workspace_beneficiary_id')
            ->where('workspace_beneficiaries.relatable_type', (new Campaign)->getMorphClass())
            ->whereIn('workspace_beneficiaries.relatable_id', $campaignIds)
            ->whereIn('consultations.status', [
                ConsultationReservationStatus::Reserved,
                ConsultationReservationStatus::Confirmed,
            ])
            ->selectRaw('workspace_beneficiaries.relatable_id AS campaign_id, consultations.consultation_type, consultations.status, COUNT(*) AS aggregate')
            ->groupBy(
                'workspace_beneficiaries.relatable_id',
                'consultations.consultation_type',
                'consultations.status',
            )
            ->get()
            ->groupBy('campaign_id');

        foreach ($campaigns as $campaign) {
            $campaign->setAttribute('financial_metrics', $this->metrics(
                campaign: $campaign,
                quotas: $quotas->get($campaign->getKey(), collect()),
                usages: $usages->get($campaign->getKey(), collect()),
                consultations: $consultations->get($campaign->getKey(), collect()),
            ));
        }
    }

    /**
     * @param  Collection<int, CampaignConsultationQuota>  $quotas
     * @param  Collection<int, CampaignUsageEntry>  $usages
     * @param  Collection<int, Consultation>  $consultations
     * @return array<string, mixed>
     */
    private function metrics(
        Campaign $campaign,
        Collection $quotas,
        Collection $usages,
        Collection $consultations,
    ): array {
        $scheduled = $this->consultationMetrics($campaign, ConsultationType::Scheduled, $quotas, $usages, $consultations);
        $instant = $this->consultationMetrics($campaign, ConsultationType::Instant, $quotas, $usages, $consultations);
        $medication = $this->budgetMetrics($campaign, CampaignBudgetCategory::Medication, $usages);
        $laboratory = $this->budgetMetrics($campaign, CampaignBudgetCategory::Laboratory, $usages);
        $allocatedMinor = $scheduled['allocated_minor'] + $instant['allocated_minor']
            + $medication['allocated_minor'] + $laboratory['allocated_minor'];
        $consultationUtilizedMinor = $scheduled['utilized_minor'] + $instant['utilized_minor'];
        $utilizedMinor = $consultationUtilizedMinor
            + $medication['used_minor'] + $laboratory['used_minor'];

        return [
            'currency' => $campaign->currency,
            'allocated' => (new Money($allocatedMinor, $campaign->currency))->toMajorAmount(),
            'utilized' => (new Money($utilizedMinor, $campaign->currency))->toMajorAmount(),
            'consultationUtilized' => (new Money($consultationUtilizedMinor, $campaign->currency))->toMajorAmount(),
            'reserved' => (new Money(max(0, $allocatedMinor - $utilizedMinor), $campaign->currency))->toMajorAmount(),
            'returned' => $campaign->returned_amount,
            'utilizationPercentage' => $allocatedMinor === 0
                ? 0
                : (int) round(($utilizedMinor / $allocatedMinor) * 100),
            'consultations' => [
                'scheduled' => [
                    'units' => $scheduled['units'],
                    'confirmed' => $scheduled['confirmed'],
                    'reserved' => $scheduled['reserved'],
                    'remaining' => $scheduled['remaining'],
                    'unitFee' => $scheduled['unitFee'],
                    'allocated' => $scheduled['allocated'],
                ],
                'instant' => [
                    'units' => $instant['units'],
                    'confirmed' => $instant['confirmed'],
                    'reserved' => $instant['reserved'],
                    'remaining' => $instant['remaining'],
                    'unitFee' => $instant['unitFee'],
                    'allocated' => $instant['allocated'],
                ],
            ],
            'budgets' => [
                'medication' => [
                    'allocated' => $medication['allocated'],
                    'used' => $medication['used'],
                    'remaining' => $medication['remaining'],
                ],
                'laboratory' => [
                    'allocated' => $laboratory['allocated'],
                    'used' => $laboratory['used'],
                    'remaining' => $laboratory['remaining'],
                ],
            ],
        ];
    }

    /**
     * @param  Collection<int, CampaignConsultationQuota>  $quotas
     * @param  Collection<int, CampaignUsageEntry>  $usages
     * @param  Collection<int, Consultation>  $consultations
     * @return array{units: int, confirmed: int, reserved: int, remaining: int, unitFee: string, allocated: string, allocated_minor: int, utilized_minor: int}
     */
    private function consultationMetrics(
        Campaign $campaign,
        ConsultationType $type,
        Collection $quotas,
        Collection $usages,
        Collection $consultations,
    ): array {
        $quota = $quotas->first(fn (CampaignConsultationQuota $row): bool => $row->consultation_type === $type);
        $units = (int) $quota?->getAttribute('units');
        $unitFee = Money::fromMajor((string) ($quota?->getAttribute('unit_fee') ?? '0.00'), $campaign->currency);
        $usage = $usages->first(fn (CampaignUsageEntry $row): bool => $row->benefit->value === $type->value);
        $confirmed = (int) $usage?->getAttribute('units_used');
        $allocated = Money::fromMajor(
            (string) ($quota?->getAttribute('total_cost') ?? '0.00'),
            $campaign->currency,
        );
        $utilized = Money::fromMajor(
            (string) ($usage?->getAttribute('used') ?? '0.00'),
            $campaign->currency,
        );
        $reserved = (int) $consultations
            ->first(fn (Consultation $row): bool => $row->consultation_type === $type && $row->status === ConsultationReservationStatus::Reserved)
            ?->getAttribute('aggregate');

        return [
            'units' => $units,
            'confirmed' => $confirmed,
            'reserved' => $reserved,
            'remaining' => max(0, $units - $confirmed - $reserved),
            'unitFee' => $unitFee->toMajorAmount(),
            'allocated' => $allocated->toMajorAmount(),
            'allocated_minor' => $allocated->amountInMinorUnits,
            'utilized_minor' => $utilized->amountInMinorUnits,
        ];
    }

    /**
     * @param  Collection<int, CampaignUsageEntry>  $budgets
     * @return array{allocated: string, used: string, remaining: string, allocated_minor: int, used_minor: int}
     */
    private function budgetMetrics(
        Campaign $campaign,
        CampaignBudgetCategory $category,
        Collection $budgets,
    ): array {
        $allocated = Money::fromMajor(
            $category === CampaignBudgetCategory::Medication
                ? $campaign->medication_budget
                : $campaign->laboratory_budget,
            $campaign->currency,
        );
        $used = Money::fromMajor(
            (string) ($budgets->first(fn (CampaignUsageEntry $row): bool => $row->benefit->value === $category->value)?->getAttribute('used') ?? '0.00'),
            $campaign->currency,
        );

        return [
            'allocated' => $allocated->toMajorAmount(),
            'used' => $used->toMajorAmount(),
            'remaining' => (new Money(max(0, $allocated->amountInMinorUnits - $used->amountInMinorUnits), $campaign->currency))->toMajorAmount(),
            'allocated_minor' => $allocated->amountInMinorUnits,
            'used_minor' => $used->amountInMinorUnits,
        ];
    }
}
