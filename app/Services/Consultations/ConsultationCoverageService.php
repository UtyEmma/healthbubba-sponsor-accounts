<?php

namespace App\Services\Consultations;

use App\DTOs\Campaigns\CampaignConsultationSummaryData;
use App\DTOs\Consultations\ConsultationAllocation;
use App\DTOs\Consultations\ConsultationAllocationScaling;
use App\DTOs\Consultations\ConsultationCoverageSummary;
use App\DTOs\Consultations\ConsultationQuotaBreakdown;
use App\DTOs\Consultations\ConsultationScalingStep;
use App\DTOs\Consultations\ConsultationUsage;
use App\Enums\AccountTypes;
use App\Enums\Appointments\AppointmentStatus;
use App\Enums\Consultations\ConsultationAllocationScope;
use App\Enums\Consultations\ConsultationReservationStatus;
use App\Enums\Consultations\ConsultationType;
use App\Enums\Subscriptions\Features;
use App\Enums\WorkspaceBeneficiaries\WorkspaceBeneficiaryStatus;
use App\Models\Consultations\Appointment;
use App\Models\Consultations\Consultation;
use App\Models\Subscription;
use App\Models\Workspace;
use App\Models\WorkspaceBeneficiary;
use App\Queries\InstitutionalCampaigns\CampaignConsultationSummaryQuery;
use App\Services\Payments\CapacityPricingService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Revoltify\Subscriptionify\Enums\Interval;
use Revoltify\Subscriptionify\Enums\SubscriptionStatus;
use Revoltify\Subscriptionify\Models\Feature;
use Revoltify\Subscriptionify\Models\FeaturePlan;

final readonly class ConsultationCoverageService
{
    public function __construct(
        private ConsultationTypeResolver $types,
        private CapacityPricingService $capacityPricing,
        private CampaignConsultationSummaryQuery $campaignConsultations,
    ) {}

    public function activeSubscription(Workspace $workspace, bool $lock = false): ?Subscription
    {
        $query = Subscription::query()
            ->where('subscribable_type', $workspace->getMorphClass())
            ->where('subscribable_id', $workspace->getKey())
            ->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::Trialing])
            ->latest('id');

        if ($lock) {
            $query->lockForUpdate();
        }

        $subscription = $query->first();

        if (! $subscription instanceof Subscription || ! $this->hasUsableTerm($subscription)) {
            return null;
        }

        $subscription->loadMissing('plan.features');

        return $subscription;
    }

    public function allocation(
        Workspace $workspace,
        Subscription $subscription,
        ConsultationType $type,
        ?WorkspaceBeneficiary $workspaceBeneficiary = null,
    ): ?ConsultationAllocation {
        if ($subscription->plan->account_type !== $workspace->type) {
            return null;
        }

        $featureSlug = $this->types->feature($workspace->type, $type);
        $limits = $this->featureAssignment($subscription, $featureSlug);

        if (! $limits instanceof FeaturePlan
            || ! $this->hasValidResetCadence($limits)) {
            return null;
        }

        [$periodStart, $periodEnd] = $this->currentPeriod(
            $subscription,
            $limits->reset_period,
            $limits->reset_interval,
        );

        if (! $periodStart instanceof CarbonImmutable || ! $periodEnd instanceof CarbonImmutable) {
            return null;
        }

        $configuredLimit = $this->nonNegativeInteger($limits);

        if ($configuredLimit === null) {
            return null;
        }

        $scope = $workspace->type === AccountTypes::BUSINESS
            ? ConsultationAllocationScope::PerEmployee
            : ConsultationAllocationScope::Shared;
        $limit = $workspace->type === AccountTypes::BUSINESS || $configuredLimit > 0
            ? $configuredLimit
            : null;

        if ($workspace->type === AccountTypes::INDIVIDUAL && $limit !== null) {
            $scaling = $this->scaling($workspace, $subscription);

            if ($scaling->available
                && $scaling->includedCapacity !== null
                && $scaling->currentCapacity !== null) {
                $perCapacity = $type === ConsultationType::GeneralPractitioner
                    ? $scaling->gpPerCapacity
                    : $scaling->specialistPerCapacity;
                $limit += max(0, $scaling->currentCapacity - $scaling->includedCapacity)
                    * ($perCapacity ?? 0);
            }
        }

        return new ConsultationAllocation(
            subscriptionId: (int) $subscription->getKey(),
            planId: (int) $subscription->plan->getKey(),
            planName: $subscription->plan->name,
            type: $type,
            featureSlug: $featureSlug->value,
            scope: $scope,
            workspaceBeneficiaryId: $workspaceBeneficiary?->getKey(),
            limit: $limit,
            periodStart: $periodStart,
            periodEnd: $periodEnd,
        );
    }

    /** @return array{planName: string|null, activeBeneficiaries: int, allocations: list<array<string, mixed>>, scaling: array<string, mixed>} */
    public function summary(Workspace $workspace): array
    {
        if ($workspace->type === AccountTypes::INSTITUTION) {
            return $this->institutionalSummary(
                $this->campaignConsultations->getForWorkspace($workspace),
            );
        }

        $subscription = $this->activeSubscription($workspace);
        $scaling = $this->scaling($workspace, $subscription);
        $activeBeneficiaries = $workspace->workspaceBeneficiaries()
            ->where('status', WorkspaceBeneficiaryStatus::Active)
            ->whereNotNull('beneficiary_id')
            ->count();
        $summaries = [];

        foreach (ConsultationType::cases() as $type) {
            if (! $subscription instanceof Subscription) {
                $summary = new ConsultationCoverageSummary(
                    type: $type,
                    scope: $workspace->type === AccountTypes::BUSINESS
                        ? ConsultationAllocationScope::PerEmployee
                        : ConsultationAllocationScope::Shared,
                    limit: 0,
                    completed: 0,
                    reserved: 0,
                    resetAt: null,
                    unavailableReason: 'An active or trialing subscription is required.',
                );
                $summaries[] = $summary->toArray();

                continue;
            }

            $allocation = $this->allocation($workspace, $subscription, $type);

            if (! $allocation instanceof ConsultationAllocation) {
                $summary = new ConsultationCoverageSummary(
                    type: $type,
                    scope: $workspace->type === AccountTypes::BUSINESS
                        ? ConsultationAllocationScope::PerEmployee
                        : ConsultationAllocationScope::Shared,
                    limit: 0,
                    completed: 0,
                    reserved: 0,
                    resetAt: null,
                    unavailableReason: 'This consultation benefit is not configured for the current plan.',
                );
                $summaries[] = $summary->toArray();

                continue;
            }

            $usage = $this->usage($workspace, $allocation);
            $limit = $allocation->limit;

            if ($allocation->scope === ConsultationAllocationScope::PerEmployee && $limit !== null) {
                $limit *= $scaling->currentCapacity ?? max(0, $subscription->capacity_count);
            }

            $summaries[] = (new ConsultationCoverageSummary(
                type: $type,
                scope: $allocation->scope,
                limit: $limit,
                completed: $usage->completed,
                reserved: $usage->reserved,
                resetAt: $allocation->periodEnd,
            ))->toArray();
        }

        return [
            'planName' => $subscription?->plan->name,
            'activeBeneficiaries' => $activeBeneficiaries,
            'allocations' => $summaries,
            'scaling' => $scaling->toArray(),
        ];
    }

    /** @return array{planName: string, activeBeneficiaries: int, allocations: list<array<string, mixed>>, scaling: array<string, mixed>} */
    private function institutionalSummary(CampaignConsultationSummaryData $summary): array
    {
        return [
            'planName' => $summary->campaignName,
            'activeBeneficiaries' => $summary->activeBeneficiaries,
            'allocations' => $summary->allocations,
            'scaling' => $this->unavailableScaling(
                capacityLabel: 'Campaign beneficiaries',
                capacityUnit: 'beneficiary',
                capacityUnitPlural: 'beneficiaries',
                reason: 'Institutional coverage uses consultation units purchased for campaigns and does not require a subscription plan.',
            )->toArray(),
        ];
    }

    private function scaling(
        Workspace $workspace,
        ?Subscription $subscription,
    ): ConsultationAllocationScaling {
        [$capacityLabel, $capacityUnit, $capacityUnitPlural] = $this->capacityTerms($workspace->type);

        if ($workspace->type === AccountTypes::INSTITUTION) {
            return $this->unavailableScaling(
                capacityLabel: $capacityLabel,
                capacityUnit: $capacityUnit,
                capacityUnitPlural: $capacityUnitPlural,
                reason: 'This plan uses fixed consultation allocations and does not scale by purchased capacity.',
            );
        }

        if (! $subscription instanceof Subscription) {
            return $this->unavailableScaling(
                capacityLabel: $capacityLabel,
                capacityUnit: $capacityUnit,
                capacityUnitPlural: $capacityUnitPlural,
                reason: 'An active or trialing subscription is required.',
            );
        }

        $capacity = $this->capacityPricing->configuration($subscription->plan);
        $includedCapacity = $capacity?->includedCapacity;
        $currentCapacity = $this->capacityPricing->currentCapacity($subscription);
        $maximumCapacity = $capacity?->maximumCapacity;

        if ($includedCapacity === null
            || $includedCapacity < 1
            || $maximumCapacity === null
            || $maximumCapacity < $includedCapacity) {
            return $this->unavailableScaling(
                capacityLabel: $capacityLabel,
                capacityUnit: $capacityUnit,
                capacityUnitPlural: $capacityUnitPlural,
                reason: 'The plan capacity limits must be configured before consultation scaling can be calculated.',
                includedCapacity: $includedCapacity,
                currentCapacity: $currentCapacity,
                maximumCapacity: $maximumCapacity,
            );
        }

        $gpPerSeat = $this->featureAssignment(
            $subscription,
            $this->types->perSeatFeature(ConsultationType::GeneralPractitioner),
        );
        $specialistPerSeat = $this->featureAssignment(
            $subscription,
            $this->types->perSeatFeature(ConsultationType::Specialist),
        );

        if (! $gpPerSeat instanceof FeaturePlan || ! $specialistPerSeat instanceof FeaturePlan) {
            return $this->unavailableScaling(
                capacityLabel: $capacityLabel,
                capacityUnit: $capacityUnit,
                capacityUnitPlural: $capacityUnitPlural,
                reason: 'GP and Specialist per-seat consultation features must be configured for this plan.',
                includedCapacity: $includedCapacity,
                currentCapacity: $currentCapacity,
                maximumCapacity: $maximumCapacity,
            );
        }

        $gpPerCapacity = $this->nonNegativeInteger($gpPerSeat);
        $specialistPerCapacity = $this->nonNegativeInteger($specialistPerSeat);

        if ($gpPerCapacity === null
            || $specialistPerCapacity === null
            || ! $this->hasValidResetCadence($gpPerSeat)
            || ! $this->hasValidResetCadence($specialistPerSeat)) {
            return $this->unavailableScaling(
                capacityLabel: $capacityLabel,
                capacityUnit: $capacityUnit,
                capacityUnitPlural: $capacityUnitPlural,
                reason: 'Per-seat consultation features require non-negative whole-number allowances and valid reset periods.',
                includedCapacity: $includedCapacity,
                currentCapacity: $currentCapacity,
                maximumCapacity: $maximumCapacity,
            );
        }

        $gpBase = null;
        $specialistBase = null;

        if ($workspace->type === AccountTypes::INDIVIDUAL) {
            $gpBaseAssignment = $this->featureAssignment(
                $subscription,
                $this->types->baseFeature(ConsultationType::GeneralPractitioner),
            );
            $specialistBaseAssignment = $this->featureAssignment(
                $subscription,
                $this->types->baseFeature(ConsultationType::Specialist),
            );

            if (! $gpBaseAssignment instanceof FeaturePlan
                || ! $specialistBaseAssignment instanceof FeaturePlan
                || ! $this->hasValidResetCadence($gpBaseAssignment)
                || ! $this->hasValidResetCadence($specialistBaseAssignment)) {
                return $this->unavailableScaling(
                    capacityLabel: $capacityLabel,
                    capacityUnit: $capacityUnit,
                    capacityUnitPlural: $capacityUnitPlural,
                    reason: 'GP and Specialist consultation features must be configured for this individual plan.',
                    includedCapacity: $includedCapacity,
                    currentCapacity: $currentCapacity,
                    maximumCapacity: $maximumCapacity,
                );
            }

            if (! $this->hasMatchingCadence($gpBaseAssignment, $gpPerSeat)
                || ! $this->hasMatchingCadence($specialistBaseAssignment, $specialistPerSeat)) {
                return $this->unavailableScaling(
                    capacityLabel: $capacityLabel,
                    capacityUnit: $capacityUnit,
                    capacityUnitPlural: $capacityUnitPlural,
                    reason: 'Each per-seat consultation feature must use the same reset cadence as its base consultation feature.',
                    includedCapacity: $includedCapacity,
                    currentCapacity: $currentCapacity,
                    maximumCapacity: $maximumCapacity,
                );
            }

            $gpBaseValue = $this->nonNegativeInteger($gpBaseAssignment);
            $specialistBaseValue = $this->nonNegativeInteger($specialistBaseAssignment);

            if ($gpBaseValue === null || $specialistBaseValue === null) {
                return $this->unavailableScaling(
                    capacityLabel: $capacityLabel,
                    capacityUnit: $capacityUnit,
                    capacityUnitPlural: $capacityUnitPlural,
                    reason: 'Base consultation features require non-negative whole-number allowances.',
                    includedCapacity: $includedCapacity,
                    currentCapacity: $currentCapacity,
                    maximumCapacity: $maximumCapacity,
                );
            }

            $gpBase = $gpBaseValue === 0 ? null : $gpBaseValue;
            $specialistBase = $specialistBaseValue === 0 ? null : $specialistBaseValue;
        } else {
            $gpBase = $includedCapacity * $gpPerCapacity;
            $specialistBase = $includedCapacity * $specialistPerCapacity;
        }

        $steps = [];
        $lastCapacity = min($includedCapacity + 4, $maximumCapacity);

        for ($projectedCapacity = $includedCapacity; $projectedCapacity <= $lastCapacity; $projectedCapacity++) {
            $additionalCapacity = $projectedCapacity - $includedCapacity;
            $steps[] = new ConsultationScalingStep(
                capacity: $projectedCapacity,
                additionalCapacity: $additionalCapacity,
                gp: $this->quotaBreakdown(
                    $gpBase,
                    $additionalCapacity * $gpPerCapacity,
                ),
                specialist: $this->quotaBreakdown(
                    $specialistBase,
                    $additionalCapacity * $specialistPerCapacity,
                ),
            );
        }

        $description = $workspace->type === AccountTypes::BUSINESS
            ? "Each extra employee seat adds +{$gpPerCapacity} GP and +{$specialistPerCapacity} specialist consultations to the workspace total; employee allowances remain separate."
            : "Each extra beneficiary adds +{$gpPerCapacity} GP and +{$specialistPerCapacity} specialist consultations to the shared pool.";

        return new ConsultationAllocationScaling(
            available: true,
            unavailableReason: null,
            capacityLabel: $capacityLabel,
            capacityUnit: $capacityUnit,
            capacityUnitPlural: $capacityUnitPlural,
            includedCapacity: $includedCapacity,
            currentCapacity: $currentCapacity,
            maximumCapacity: $maximumCapacity,
            gpPerCapacity: $gpPerCapacity,
            specialistPerCapacity: $specialistPerCapacity,
            description: $description,
            steps: $steps,
        );
    }

    private function unavailableScaling(
        string $capacityLabel,
        string $capacityUnit,
        string $capacityUnitPlural,
        string $reason,
        ?int $includedCapacity = null,
        ?int $currentCapacity = null,
        ?int $maximumCapacity = null,
    ): ConsultationAllocationScaling {
        return new ConsultationAllocationScaling(
            available: false,
            unavailableReason: $reason,
            capacityLabel: $capacityLabel,
            capacityUnit: $capacityUnit,
            capacityUnitPlural: $capacityUnitPlural,
            includedCapacity: $includedCapacity,
            currentCapacity: $currentCapacity,
            maximumCapacity: $maximumCapacity,
            gpPerCapacity: null,
            specialistPerCapacity: null,
            description: $reason,
            steps: [],
        );
    }

    private function quotaBreakdown(?int $base, int $additional): ConsultationQuotaBreakdown
    {
        if ($base === null) {
            return new ConsultationQuotaBreakdown(
                base: null,
                additional: null,
                total: null,
            );
        }

        return new ConsultationQuotaBreakdown(
            base: $base,
            additional: $additional,
            total: $base + $additional,
        );
    }

    /** @return array{string, string, string} */
    private function capacityTerms(AccountTypes $accountType): array
    {
        return match ($accountType) {
            AccountTypes::INDIVIDUAL => ['Beneficiaries', 'beneficiary', 'beneficiaries'],
            AccountTypes::BUSINESS => ['Employee seats', 'employee seat', 'employee seats'],
            AccountTypes::INSTITUTION => ['Capacity', 'capacity unit', 'capacity units'],
        };
    }

    private function featureAssignment(
        Subscription $subscription,
        Features $featureType,
    ): ?FeaturePlan {
        $feature = $subscription->plan->features->first(
            static fn (Feature $feature): bool => $feature->getRawOriginal('slug') === $featureType->value,
        );

        if (! $feature instanceof Feature || ! $feature->relationLoaded('limits')) {
            return null;
        }

        $assignment = $feature->getRelation('limits');

        return $assignment instanceof FeaturePlan ? $assignment : null;
    }

    private function nonNegativeInteger(FeaturePlan $assignment): ?int
    {
        $value = $assignment->getValue();

        if (! ctype_digit($value)) {
            return null;
        }

        return (int) $value;
    }

    private function hasValidResetCadence(FeaturePlan $assignment): bool
    {
        return $assignment->reset_period !== null
            && $assignment->reset_period > 0
            && $assignment->reset_interval instanceof Interval;
    }

    private function hasMatchingCadence(FeaturePlan $base, FeaturePlan $perSeat): bool
    {
        return $base->reset_period === $perSeat->reset_period
            && $base->reset_interval === $perSeat->reset_interval;
    }

    public function usage(Workspace $workspace, ConsultationAllocation $allocation): ConsultationUsage
    {
        $ledger = $this->ledgerForAllocation($workspace, $allocation);
        $ledgerAppointmentIds = $ledger
            ->pluck('appointment_id')
            ->filter()
            ->map(static fn (mixed $id): int => (int) $id)
            ->values();
        $ledgerAppointments = $ledgerAppointmentIds->isEmpty()
            ? new Collection
            : Appointment::query()
                ->select(['appointment_id', 'patient_id', 'doctor_id', 'status'])
                ->whereKey($ledgerAppointmentIds->all())
                ->get()
                ->keyBy('appointment_id');

        $completed = 0;
        $reserved = 0;

        foreach ($ledger as $reservation) {
            $appointment = $reservation->appointment_id === null
                ? null
                : $ledgerAppointments->get($reservation->appointment_id);

            if ($appointment instanceof Appointment && $appointment->status === AppointmentStatus::Cancelled) {
                continue;
            }

            if ($appointment instanceof Appointment && $appointment->status === AppointmentStatus::Completed) {
                $completed++;
            } else {
                $reserved++;
            }
        }

        $legacyAppointments = $this->sponsoredAppointmentsForAllocation($workspace, $allocation)
            ->reject(static fn (Appointment $appointment): bool => $ledgerAppointmentIds->contains($appointment->getKey()));

        foreach ($legacyAppointments as $appointment) {
            $appointmentType = $this->types->resolve($appointment->doctor?->provider_type);

            if ($appointmentType !== $allocation->type) {
                continue;
            }

            if ($appointment->status === AppointmentStatus::Completed) {
                $completed++;
            } else {
                $reserved++;
            }
        }

        return new ConsultationUsage($completed, $reserved);
    }

    /** @return Collection<int, Consultation> */
    private function ledgerForAllocation(Workspace $workspace, ConsultationAllocation $allocation): Collection
    {
        $query = Consultation::query()
            ->whereBelongsTo($workspace)
            ->where('consultation_type', $allocation->type)
            ->where('allocation_period_start', $allocation->periodStart)
            ->where('allocation_period_end', $allocation->periodEnd)
            ->whereIn('status', [
                ConsultationReservationStatus::Reserved,
                ConsultationReservationStatus::Confirmed,
            ]);

        if ($allocation->workspaceBeneficiaryId !== null) {
            $query->where('workspace_beneficiary_id', $allocation->workspaceBeneficiaryId);
        }

        return $query->get(['id', 'appointment_id', 'status']);
    }

    /** @return Collection<int, Appointment> */
    private function sponsoredAppointmentsForAllocation(
        Workspace $workspace,
        ConsultationAllocation $allocation,
    ): Collection {
        $query = Appointment::query()
            ->select(['appointment_id', 'patient_id', 'doctor_id', 'status', 'created_at'])
            ->with(['doctor:id,first_name,last_name,provider_type'])
            ->sponsoredBy($workspace)
            ->whereIn('status', [AppointmentStatus::Upcoming, AppointmentStatus::Completed])
            ->whereBetween('created_at', [$allocation->periodStart, $allocation->periodEnd]);

        if ($allocation->workspaceBeneficiaryId !== null) {
            $beneficiaryId = WorkspaceBeneficiary::query()
                ->whereKey($allocation->workspaceBeneficiaryId)
                ->value('beneficiary_id');

            $query->where('patient_id', $beneficiaryId ?? 0);
        }

        return $query->get();
    }

    /** @return array{CarbonImmutable|null, CarbonImmutable|null} */
    private function currentPeriod(
        Subscription $subscription,
        int $resetPeriod,
        Interval $resetInterval,
    ): array {
        $now = CarbonImmutable::now();
        $periodStart = $subscription->starts_at->toImmutable();

        if ($periodStart->isFuture()) {
            return [null, null];
        }

        $periodEnd = $resetInterval->addToDate($periodStart, $resetPeriod)->toImmutable();

        while ($periodEnd->lessThanOrEqualTo($now)) {
            $periodStart = $periodEnd;
            $periodEnd = $resetInterval->addToDate($periodStart, $resetPeriod)->toImmutable();
        }

        $termEnd = $subscription->status === SubscriptionStatus::Trialing
            ? $subscription->trial_ends_at?->toImmutable()
            : $subscription->ends_at?->toImmutable();

        if ($termEnd instanceof CarbonImmutable) {
            if ($termEnd->lessThanOrEqualTo($now) || $periodStart->greaterThanOrEqualTo($termEnd)) {
                return [null, null];
            }

            if ($periodEnd->greaterThan($termEnd)) {
                $periodEnd = $termEnd;
            }
        }

        return [$periodStart, $periodEnd];
    }

    private function hasUsableTerm(Subscription $subscription): bool
    {
        if ($subscription->starts_at->isFuture()) {
            return false;
        }

        if ($subscription->status === SubscriptionStatus::Trialing) {
            return $subscription->trial_ends_at?->isFuture() === true;
        }

        return $subscription->ends_at === null || $subscription->ends_at->isFuture();
    }
}
