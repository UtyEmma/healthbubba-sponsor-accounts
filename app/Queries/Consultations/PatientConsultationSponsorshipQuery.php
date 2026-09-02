<?php

namespace App\Queries\Consultations;

use App\DTOs\Consultations\ConsultationSponsorAvailabilityData;
use App\DTOs\Consultations\ConsultationTypeAvailabilityData;
use App\DTOs\Consultations\PatientConsultationSponsorshipData;
use App\Enums\AccountTypes;
use App\Enums\CampaignUsageBenefit;
use App\Enums\Consultations\ConsultationReservationStatus;
use App\Enums\Consultations\ConsultationType;
use App\Enums\InstitutionalCoverageType;
use App\Enums\WorkspaceBeneficiaries\WorkspaceBeneficiaryStatus;
use App\Models\Beneficiary;
use App\Models\Campaign;
use App\Models\CampaignUsageEntry;
use App\Models\Consultations\Consultation;
use App\Models\InstitutionalFundingProgram;
use App\Models\Subscription;
use App\Models\Workspace;
use App\Models\WorkspaceBeneficiary;
use App\Services\Consultations\ConsultationCoverageService;
use App\Services\Funding\InstitutionalCoverageRulesResolver;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

final readonly class PatientConsultationSponsorshipQuery
{
    public function __construct(
        private ConsultationCoverageService $coverage,
        private InstitutionalCoverageRulesResolver $institutionalRules,
    ) {}

    public function getForPatient(Beneficiary $patient): PatientConsultationSponsorshipData
    {
        $memberships = WorkspaceBeneficiary::query()
            ->with('workspace')
            ->where('beneficiary_id', $patient->getKey())
            ->where('status', WorkspaceBeneficiaryStatus::Active)
            ->orderBy('workspace_id')
            ->orderBy('id')
            ->get();

        $sponsors = $memberships
            ->groupBy('workspace_id')
            ->map(function (Collection $workspaceMemberships) use ($patient): ?ConsultationSponsorAvailabilityData {
                $membership = $workspaceMemberships->first();

                if (! $membership instanceof WorkspaceBeneficiary) {
                    return null;
                }

                $workspace = $membership->workspace;

                if ($workspace->type === AccountTypes::INSTITUTION) {
                    /** @var EloquentCollection<int, WorkspaceBeneficiary> $institutionalMemberships */
                    $institutionalMemberships = new EloquentCollection(
                        $workspaceMemberships->values()->all(),
                    );

                    return $this->institutionalSponsor(
                        $workspace,
                        $institutionalMemberships,
                        (int) $patient->getKey(),
                    );
                }

                return $this->subscriptionSponsor($workspace, $membership);
            })
            ->filter(fn (mixed $sponsor): bool => $sponsor instanceof ConsultationSponsorAvailabilityData)
            ->values()
            ->all();

        return new PatientConsultationSponsorshipData(
            patientId: (int) $patient->getKey(),
            sponsors: array_values($sponsors),
        );
    }

    private function subscriptionSponsor(
        Workspace $workspace,
        WorkspaceBeneficiary $membership,
    ): ConsultationSponsorAvailabilityData {
        $subscription = $this->coverage->activeSubscription($workspace);
        $types = [];

        foreach (ConsultationType::cases() as $type) {
            if (! $subscription instanceof Subscription) {
                $types[] = $this->unavailableType($type, 'no_active_subscription');

                continue;
            }

            $allocation = $this->coverage->allocation(
                $workspace,
                $subscription,
                $type,
                $membership,
            );

            if ($allocation === null) {
                $types[] = $this->unavailableType(
                    $type,
                    'feature_unavailable',
                    $subscription->plan->name,
                );

                continue;
            }

            $usage = $this->coverage->usage($workspace, $allocation);
            $remaining = $allocation->limit === null
                ? null
                : max(0, $allocation->limit - $usage->total());

            $types[] = new ConsultationTypeAvailabilityData(
                type: $type,
                available: $remaining === null || $remaining > 0,
                reason: $remaining === 0 ? 'allocation_exhausted' : null,
                coverageName: $allocation->planName,
                allocatedUnits: $allocation->limit,
                usedUnits: $usage->completed,
                reservedUnits: $usage->reserved,
                remainingUnits: $remaining,
                periodStartsAt: $allocation->periodStart,
                periodEndsAt: $allocation->periodEnd,
            );
        }

        return $this->sponsor($workspace, $types);
    }

    /** @param EloquentCollection<int, WorkspaceBeneficiary> $memberships */
    private function institutionalSponsor(
        Workspace $workspace,
        EloquentCollection $memberships,
        int $patientId,
    ): ConsultationSponsorAvailabilityData {
        $program = InstitutionalFundingProgram::query()
            ->whereBelongsTo($workspace)
            ->first();

        if (! $program instanceof InstitutionalFundingProgram) {
            return $this->sponsor(
                $workspace,
                array_map(
                    fn (ConsultationType $type): ConsultationTypeAvailabilityData => $this->unavailableType(
                        $type,
                        'no_funding_program',
                    ),
                    ConsultationType::cases(),
                ),
            );
        }

        $campaignMorph = (new Campaign)->getMorphClass();
        $campaignMemberships = $memberships
            ->where('relatable_type', $campaignMorph)
            ->sortBy([['relatable_id', 'asc'], ['id', 'asc']])
            ->values();
        $campaignIds = $campaignMemberships
            ->pluck('relatable_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        $campaigns = Campaign::query()
            ->with('consultationQuotas')
            ->whereBelongsTo($workspace)
            ->whereKey($campaignIds->all())
            ->orderBy('id')
            ->get()
            ->filter(fn (Campaign $campaign): bool => $campaign->isActive())
            ->keyBy('id');

        if ($campaigns->isEmpty()) {
            return $this->sponsor(
                $workspace,
                array_map(
                    fn (ConsultationType $type): ConsultationTypeAvailabilityData => $this->unavailableType(
                        $type,
                        'no_active_campaign',
                    ),
                    ConsultationType::cases(),
                ),
            );
        }

        $period = $this->institutionalRules->resolve($campaigns->first(), $program);
        $activeCampaignIds = array_values(
            $campaigns->keys()->map(fn (mixed $id): int => (int) $id)->all(),
        );
        $campaignUsed = $this->campaignUsed($activeCampaignIds);
        $campaignReserved = $this->campaignReserved($activeCampaignIds);
        $beneficiaryUsed = $this->campaignUsed(
            $activeCampaignIds,
            patientId: $patientId,
            periodStartsAt: $period->periodStart,
            periodEndsAt: $period->periodEnd,
        );
        $beneficiaryReserved = $this->campaignReserved(
            $activeCampaignIds,
            patientId: $patientId,
            periodStartsAt: $period->periodStart,
            periodEndsAt: $period->periodEnd,
        );
        $dailyReserved = Consultation::query()
            ->whereBelongsTo($workspace)
            ->where('beneficiary_id', $patientId)
            ->where('status', ConsultationReservationStatus::Reserved)
            ->whereBetween('reserved_at', [now()->startOfDay(), now()->endOfDay()])
            ->count();
        $dailyUsed = (int) CampaignUsageEntry::query()
            ->whereBelongsTo($workspace)
            ->whereIn('workspace_beneficiary_id', WorkspaceBeneficiary::query()
                ->whereBelongsTo($workspace)
                ->where('beneficiary_id', $patientId)
                ->select('id'))
            ->whereIn('benefit', [
                CampaignUsageBenefit::GeneralPractitioner,
                CampaignUsageBenefit::Specialist,
            ])
            ->whereBetween('occurred_at', [now()->startOfDay(), now()->endOfDay()])
            ->sum('quantity');
        $dailyUsage = $dailyReserved + $dailyUsed;
        $types = [];

        foreach (ConsultationType::cases() as $type) {
            $types[] = $this->institutionalType(
                type: $type,
                program: $program,
                campaignMemberships: $campaignMemberships,
                campaigns: $campaigns,
                campaignUsed: $campaignUsed,
                campaignReserved: $campaignReserved,
                beneficiaryUsed: $beneficiaryUsed,
                beneficiaryReserved: $beneficiaryReserved,
                dailyUsage: $dailyUsage,
            );
        }

        return $this->sponsor($workspace, $types);
    }

    /**
     * @param  Collection<int, WorkspaceBeneficiary>  $campaignMemberships
     * @param  Collection<int, Campaign>  $campaigns
     * @param  array<string, int>  $campaignUsed
     * @param  array<string, int>  $campaignReserved
     * @param  array<string, int>  $beneficiaryUsed
     * @param  array<string, int>  $beneficiaryReserved
     */
    private function institutionalType(
        ConsultationType $type,
        InstitutionalFundingProgram $program,
        Collection $campaignMemberships,
        Collection $campaigns,
        array $campaignUsed,
        array $campaignReserved,
        array $beneficiaryUsed,
        array $beneficiaryReserved,
        int $dailyUsage,
    ): ConsultationTypeAvailabilityData {
        $unavailable = $this->unavailableType($type, 'allocation_exhausted');

        foreach ($campaignMemberships as $membership) {
            $campaign = $campaigns->get($membership->relatable_id);

            if (! $campaign instanceof Campaign) {
                continue;
            }

            $rules = $this->institutionalRules->resolve($campaign, $program);
            $allocated = (int) $campaign->consultationQuotas
                ->where('consultation_type', $type)
                ->sum('quantity');
            $used = $campaignUsed[$this->usageKey($campaign->getKey(), $type)] ?? 0;
            $reserved = $campaignReserved[$this->usageKey($campaign->getKey(), $type)] ?? 0;
            $allocationRemaining = max(0, $allocated - $used - $reserved);
            $dailyRemaining = max(0, $rules->dailyConsultationLimit - $dailyUsage);
            $beneficiaryRemaining = null;

            if ($rules->coverageType === InstitutionalCoverageType::PerBeneficiary) {
                $beneficiaryUsedForType = $beneficiaryUsed[
                    $this->usageKey($campaign->getKey(), $type)
                ] ?? 0;
                $beneficiaryReservedForType = $beneficiaryReserved[
                    $this->usageKey($campaign->getKey(), $type)
                ] ?? 0;
                $beneficiaryRemaining = max(
                    0,
                    $this->institutionalRules->beneficiaryLimit($rules, $type)
                        - $beneficiaryUsedForType
                        - $beneficiaryReservedForType,
                );
            }

            $remaining = min(
                $allocationRemaining,
                $dailyRemaining,
                $beneficiaryRemaining ?? PHP_INT_MAX,
            );
            $reason = match (true) {
                $dailyRemaining === 0 => 'daily_limit_reached',
                $beneficiaryRemaining === 0 => 'per_beneficiary_limit_reached',
                $allocationRemaining === 0 => 'allocation_exhausted',
                default => null,
            };
            $availability = new ConsultationTypeAvailabilityData(
                type: $type,
                available: $remaining > 0,
                reason: $reason,
                coverageName: $campaign->name,
                allocatedUnits: $allocated,
                usedUnits: $used,
                reservedUnits: $reserved,
                remainingUnits: $remaining,
                periodStartsAt: $rules->periodStart,
                periodEndsAt: $rules->periodEnd,
            );

            if ($availability->available) {
                return $availability;
            }

            if ($unavailable->coverageName === null) {
                $unavailable = $availability;
            }
        }

        return $unavailable;
    }

    /**
     * @param  list<int>  $campaignIds
     * @return array<string, int>
     */
    private function campaignUsed(
        array $campaignIds,
        ?int $patientId = null,
        ?CarbonImmutable $periodStartsAt = null,
        ?CarbonImmutable $periodEndsAt = null,
    ): array {
        if ($campaignIds === []) {
            return [];
        }

        $query = CampaignUsageEntry::query()
            ->join('workspace_beneficiaries', 'workspace_beneficiaries.id', '=', 'campaign_usage_entries.workspace_beneficiary_id')
            ->where('workspace_beneficiaries.relatable_type', (new Campaign)->getMorphClass())
            ->whereIn('workspace_beneficiaries.relatable_id', $campaignIds)
            ->whereIn('campaign_usage_entries.benefit', [
                CampaignUsageBenefit::GeneralPractitioner,
                CampaignUsageBenefit::Specialist,
            ]);

        if ($patientId !== null) {
            $query->where('workspace_beneficiaries.beneficiary_id', $patientId);
        }

        if ($periodStartsAt !== null && $periodEndsAt !== null) {
            $query->whereBetween('campaign_usage_entries.occurred_at', [$periodStartsAt, $periodEndsAt]);
        }

        return $query
            ->selectRaw('campaign_usage_entries.campaign_id as campaign_id')
            ->selectRaw('campaign_usage_entries.benefit as benefit')
            ->selectRaw('COALESCE(SUM(campaign_usage_entries.quantity), 0) as aggregate')
            ->groupBy(
                'campaign_usage_entries.campaign_id',
                'campaign_usage_entries.benefit',
            )
            ->get()
            ->mapWithKeys(function (CampaignUsageEntry $row): array {
                $campaignId = (int) $row->getAttribute('campaign_id');
                $benefitAttribute = $row->getAttribute('benefit');
                $benefit = $benefitAttribute instanceof CampaignUsageBenefit
                    ? $benefitAttribute
                    : CampaignUsageBenefit::from((string) $benefitAttribute);
                $type = ConsultationType::from($benefit->value);

                return [
                    $this->usageKey($campaignId, $type) => (int) $row->getAttribute('aggregate'),
                ];
            })
            ->all();
    }

    /**
     * @param  list<int>  $campaignIds
     * @return array<string, int>
     */
    private function campaignReserved(
        array $campaignIds,
        ?int $patientId = null,
        ?CarbonImmutable $periodStartsAt = null,
        ?CarbonImmutable $periodEndsAt = null,
    ): array {
        if ($campaignIds === []) {
            return [];
        }

        $query = Consultation::query()
            ->join('workspace_beneficiaries', 'workspace_beneficiaries.id', '=', 'consultations.workspace_beneficiary_id')
            ->where('workspace_beneficiaries.relatable_type', (new Campaign)->getMorphClass())
            ->whereIn('workspace_beneficiaries.relatable_id', $campaignIds)
            ->where('consultations.status', ConsultationReservationStatus::Reserved);

        if ($patientId !== null) {
            $query->where('consultations.beneficiary_id', $patientId);
        }

        if ($periodStartsAt !== null && $periodEndsAt !== null) {
            $query->whereBetween('consultations.reserved_at', [$periodStartsAt, $periodEndsAt]);
        }

        return $query
            ->selectRaw('workspace_beneficiaries.relatable_id as campaign_id')
            ->selectRaw('consultations.consultation_type as consultation_type')
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy(
                'workspace_beneficiaries.relatable_id',
                'consultations.consultation_type',
            )
            ->get()
            ->mapWithKeys(function (Consultation $row): array {
                $campaignId = (int) $row->getAttribute('campaign_id');
                $typeAttribute = $row->getAttribute('consultation_type');
                $type = $typeAttribute instanceof ConsultationType
                    ? $typeAttribute
                    : ConsultationType::from((string) $typeAttribute);

                return [
                    $this->usageKey($campaignId, $type) => (int) $row->getAttribute('aggregate'),
                ];
            })
            ->all();
    }

    /** @param list<ConsultationTypeAvailabilityData> $types */
    private function sponsor(Workspace $workspace, array $types): ConsultationSponsorAvailabilityData
    {
        return new ConsultationSponsorAvailabilityData(
            id: (int) $workspace->getKey(),
            name: $workspace->name,
            type: $workspace->type,
            consultationTypes: $types,
        );
    }

    private function unavailableType(
        ConsultationType $type,
        string $reason,
        ?string $coverageName = null,
    ): ConsultationTypeAvailabilityData {
        return new ConsultationTypeAvailabilityData(
            type: $type,
            available: false,
            reason: $reason,
            coverageName: $coverageName,
            allocatedUnits: null,
            usedUnits: 0,
            reservedUnits: 0,
            remainingUnits: 0,
            periodStartsAt: null,
            periodEndsAt: null,
        );
    }

    private function usageKey(
        int $campaignId,
        ConsultationType $type,
    ): string {
        return "{$campaignId}:{$type->value}";
    }
}
