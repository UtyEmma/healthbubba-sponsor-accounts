<?php

namespace App\Services\Consultations;

use App\DTOs\Consultations\ConsultationAllocation;
use App\DTOs\Consultations\ConsultationCoverageSummary;
use App\DTOs\Consultations\ConsultationUsage;
use App\Enums\AccountTypes;
use App\Enums\Appointments\AppointmentStatus;
use App\Enums\Consultations\ConsultationAllocationScope;
use App\Enums\Consultations\ConsultationReservationStatus;
use App\Enums\Consultations\ConsultationType;
use App\Enums\WorkspaceBeneficiaries\WorkspaceBeneficiaryStatus;
use App\Models\Consultations\Appointment;
use App\Models\Consultations\Consultation;
use App\Models\Subscription;
use App\Models\Workspace;
use App\Models\WorkspaceBeneficiary;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Revoltify\Subscriptionify\Enums\Interval;
use Revoltify\Subscriptionify\Enums\SubscriptionStatus;
use Revoltify\Subscriptionify\Models\Feature;
use Revoltify\Subscriptionify\Models\FeaturePlan;

final readonly class ConsultationCoverageService
{
    public function __construct(private ConsultationTypeResolver $types) {}

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
        $feature = $subscription->plan->features->first(
            static fn (Feature $feature): bool => $feature->getRawOriginal('slug') === $featureSlug->value,
        );

        if (! $feature instanceof Feature) {
            return null;
        }

        $limits = $feature->getRelation('limits');

        if (! $limits instanceof FeaturePlan
            || $limits->reset_period === null
            || $limits->reset_period < 1
            || ! $limits->reset_interval instanceof Interval) {
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

        $configuredLimit = (int) $limits->value;
        $scope = $workspace->type === AccountTypes::BUSINESS
            ? ConsultationAllocationScope::PerEmployee
            : ConsultationAllocationScope::Shared;

        return new ConsultationAllocation(
            subscriptionId: (int) $subscription->getKey(),
            planId: (int) $subscription->plan->getKey(),
            planName: $subscription->plan->name,
            type: $type,
            featureSlug: $featureSlug->value,
            scope: $scope,
            workspaceBeneficiaryId: $workspaceBeneficiary?->getKey(),
            limit: $configuredLimit === 0 ? null : max(0, $configuredLimit),
            periodStart: $periodStart,
            periodEnd: $periodEnd,
        );
    }

    /** @return array{planName: string|null, activeBeneficiaries: int, allocations: list<array<string, mixed>>} */
    public function summary(Workspace $workspace): array
    {
        $subscription = $this->activeSubscription($workspace);
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
                $limit *= $activeBeneficiaries;
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
        ];
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
