<?php

namespace App\Queries\Consultations;

use App\DTOs\Consultations\ConsultationCampaignAvailabilityData;
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
            ->flatMap(function (Collection $workspaceMemberships) use ($patient): array {
                $membership = $workspaceMemberships->first();

                if (! $membership instanceof WorkspaceBeneficiary) {
                    return [];
                }

                $workspace = $membership->workspace;

                if ($workspace->type === AccountTypes::INSTITUTION) {
                    /** @var EloquentCollection<int, WorkspaceBeneficiary> $institutionalMemberships */
                    $institutionalMemberships = new EloquentCollection(
                        $workspaceMemberships->values()->all(),
                    );

                    return $this->institutionalSponsors(
                        $workspace,
                        $institutionalMemberships,
                        (int) $patient->getKey(),
                    );
                }

                return [$this->subscriptionSponsor($workspace, $membership)];
            })
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
    /**
     * @param  EloquentCollection<int, WorkspaceBeneficiary>  $memberships
     * @return list<ConsultationSponsorAvailabilityData>
     */
    private function institutionalSponsors(
        Workspace $workspace,
        EloquentCollection $memberships,
        int $patientId,
    ): array {
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
            return [];
        }

        $program = InstitutionalFundingProgram::query()
            ->whereBelongsTo($workspace)
            ->first();

        if (! $program instanceof InstitutionalFundingProgram) {
            return array_values($campaigns
                ->map(fn (Campaign $campaign): ConsultationSponsorAvailabilityData => $this->sponsor(
                    $workspace,
                    array_map(
                        fn (ConsultationType $type): ConsultationTypeAvailabilityData => $this->unavailableType(
                            $type,
                            'no_funding_program',
                            $campaign->name,
                        ),
                        ConsultationType::cases(),
                    ),
                    $campaign,
                ))
                ->all());
        }

        $activeCampaignIds = array_values(
            $campaigns->keys()->map(fn (mixed $id): int => (int) $id)->all(),
        );
        $campaignUsed = $this->campaignUsed($activeCampaignIds);
        $campaignReserved = $this->campaignReserved($activeCampaignIds);
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

        return array_values($campaigns
            ->map(function (Campaign $campaign) use (
                $workspace,
                $program,
                $patientId,
                $campaignUsed,
                $campaignReserved,
                $dailyUsage,
            ): ConsultationSponsorAvailabilityData {
                $rules = $this->institutionalRules->resolve($campaign, $program);
                $beneficiaryUsed = $this->campaignUsed(
                    [(int) $campaign->getKey()],
                    patientId: $patientId,
                    periodStartsAt: $rules->periodStart,
                    periodEndsAt: $rules->periodEnd,
                );
                $beneficiaryReserved = $this->campaignReserved(
                    [(int) $campaign->getKey()],
                    patientId: $patientId,
                    periodStartsAt: $rules->periodStart,
                    periodEndsAt: $rules->periodEnd,
                );
                $types = array_map(
                    fn (ConsultationType $type): ConsultationTypeAvailabilityData => $this->institutionalCampaignType(
                        type: $type,
                        campaign: $campaign,
                        program: $program,
                        campaignUsed: $campaignUsed,
                        campaignReserved: $campaignReserved,
                        beneficiaryUsed: $beneficiaryUsed,
                        beneficiaryReserved: $beneficiaryReserved,
                        dailyUsage: $dailyUsage,
                    ),
                    ConsultationType::cases(),
                );

                return $this->sponsor($workspace, $types, $campaign);
            })
            ->all());
    }

    /**
     * @param  array<string, int>  $campaignUsed
     * @param  array<string, int>  $campaignReserved
     * @param  array<string, int>  $beneficiaryUsed
     * @param  array<string, int>  $beneficiaryReserved
     */
    private function institutionalCampaignType(
        ConsultationType $type,
        Campaign $campaign,
        InstitutionalFundingProgram $program,
        array $campaignUsed,
        array $campaignReserved,
        array $beneficiaryUsed,
        array $beneficiaryReserved,
        int $dailyUsage,
    ): ConsultationTypeAvailabilityData {
        $rules = $this->institutionalRules->resolve($campaign, $program);
        $allocated = (int) $campaign->consultationQuotas
            ->where('consultation_type', $type)
            ->sum('quantity');
        $campaignUsedUnits = $campaignUsed[$this->usageKey($campaign->getKey(), $type)] ?? 0;
        $campaignReservedUnits = $campaignReserved[$this->usageKey($campaign->getKey(), $type)] ?? 0;
        $beneficiaryUsedUnits = $beneficiaryUsed[$this->usageKey($campaign->getKey(), $type)] ?? 0;
        $beneficiaryReservedUnits = $beneficiaryReserved[$this->usageKey($campaign->getKey(), $type)] ?? 0;
        $allocationRemaining = max(0, $allocated - $campaignUsedUnits - $campaignReservedUnits);
        $dailyRemaining = max(0, $rules->dailyConsultationLimit - $dailyUsage);
        $beneficiaryRemaining = null;
        $beneficiaryLimit = null;

        if ($rules->coverageType === InstitutionalCoverageType::PerBeneficiary) {
            $beneficiaryLimit = $this->institutionalRules->beneficiaryLimit($rules, $type);
            $beneficiaryRemaining = max(
                0,
                $beneficiaryLimit - $beneficiaryUsedUnits - $beneficiaryReservedUnits,
            );
        }

        $remaining = min($allocationRemaining, $dailyRemaining, $beneficiaryRemaining ?? PHP_INT_MAX);

        return new ConsultationTypeAvailabilityData(
            type: $type,
            available: $remaining > 0,
            reason: match (true) {
                $dailyRemaining === 0 => 'daily_limit_reached',
                $beneficiaryRemaining === 0 => 'per_beneficiary_limit_reached',
                $allocationRemaining === 0 => 'allocation_exhausted',
                default => null,
            },
            coverageName: $campaign->name,
            allocatedUnits: $beneficiaryLimit ?? $allocated,
            usedUnits: $beneficiaryUsedUnits,
            reservedUnits: $beneficiaryReservedUnits,
            remainingUnits: $remaining,
            periodStartsAt: $rules->periodStart,
            periodEndsAt: $rules->periodEnd,
        );
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
    private function sponsor(
        Workspace $workspace,
        array $types,
        ?Campaign $campaign = null,
    ): ConsultationSponsorAvailabilityData {
        return new ConsultationSponsorAvailabilityData(
            id: (int) $workspace->getKey(),
            name: $workspace->name,
            type: $workspace->type,
            consultationTypes: $types,
            campaign: $campaign === null ? null : new ConsultationCampaignAvailabilityData(
                id: (int) $campaign->getKey(),
                name: $campaign->name,
                slug: $campaign->slug,
                description: $campaign->description,
                location: $campaign->location,
                city: $campaign->city,
                state: $campaign->state,
                country: $campaign->country,
                status: $campaign->lifecycleStatus(),
                startsAt: $campaign->start_date?->toImmutable(),
                endsAt: $campaign->end_date?->toImmutable(),
            ),
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
