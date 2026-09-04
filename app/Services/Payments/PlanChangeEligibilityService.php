<?php

namespace App\Services\Payments;

use App\DTOs\Consultations\ConsultationAllocation;
use App\DTOs\Payments\PlanChangeEligibility;
use App\Enums\AccountTypes;
use App\Enums\Appointments\AppointmentStatus;
use App\Enums\Consultations\ConsultationReservationStatus;
use App\Enums\Consultations\ConsultationType;
use App\Models\Consultations\Appointment;
use App\Models\Consultations\Consultation;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Workspace;
use App\Services\Consultations\ConsultationCoverageService;
use App\Services\Consultations\ConsultationTypeResolver;
use App\Services\WorkspaceBeneficiaries\WorkspaceBeneficiaryCapacityService;
use Illuminate\Support\Collection;

final readonly class PlanChangeEligibilityService
{
    public function __construct(
        private CapacityPricingService $capacityPricing,
        private WorkspaceBeneficiaryCapacityService $beneficiaryCapacity,
        private ConsultationCoverageService $consultationCoverage,
        private ConsultationTypeResolver $consultationTypes,
    ) {}

    public function assess(
        Workspace $workspace,
        Subscription $subscription,
        Plan $targetPlan,
    ): PlanChangeEligibility {
        $occupiedCapacity = $this->beneficiaryCapacity->used($workspace);
        $configuration = $this->capacityPricing->configuration($targetPlan);
        $includedCapacity = max(1, $configuration?->includedCapacity ?? 1);
        $targetCapacity = max($includedCapacity, $occupiedCapacity);
        $violations = [];

        if ($configuration === null) {
            $violations[] = 'The selected plan does not define a beneficiary or employee capacity.';
        } elseif ($configuration->maximumCapacity !== null
            && $occupiedCapacity > $configuration->maximumCapacity) {
            $violations[] = sprintf(
                'You currently have %d %s, but %s supports at most %d.',
                $occupiedCapacity,
                $configuration->unitPlural,
                $targetPlan->name,
                $configuration->maximumCapacity,
            );
        } elseif ($targetCapacity > $includedCapacity
            && (! $configuration->purchasesEnabled || $configuration->unitPrice === null)) {
            $violations[] = sprintf(
                'You currently use %d %s, but %s includes %d and cannot support additional capacity.',
                $occupiedCapacity,
                $configuration->unitPlural,
                $targetPlan->name,
                $includedCapacity,
            );
        }

        $projectedSubscription = clone $subscription;
        $projectedSubscription->setAttribute('plan_id', $targetPlan->getKey());
        $projectedSubscription->setAttribute('capacity_count', $targetCapacity);
        $projectedSubscription->setRelation('plan', $targetPlan);

        foreach (ConsultationType::cases() as $type) {
            $targetAllocation = $this->consultationCoverage->allocation(
                $workspace,
                $projectedSubscription,
                $type,
            );
            $currentAllocation = $this->consultationCoverage->allocation(
                $workspace,
                $subscription,
                $type,
            );
            $period = $targetAllocation ?? $currentAllocation;

            if (! $period instanceof ConsultationAllocation) {
                continue;
            }

            $usage = $this->usageByBeneficiary($workspace, $period);
            $totalUsage = $usage->sum();

            if (! $targetAllocation instanceof ConsultationAllocation) {
                if ($totalUsage > 0) {
                    $violations[] = sprintf(
                        '%s has already been used or reserved during this period, but it is unavailable on %s.',
                        $type->label(),
                        $targetPlan->name,
                    );
                }

                continue;
            }

            if ($targetAllocation->limit === null) {
                continue;
            }

            if ($workspace->type === AccountTypes::BUSINESS) {
                $highestUsage = (int) ($usage->max() ?? 0);

                if ($highestUsage > $targetAllocation->limit) {
                    $violations[] = sprintf(
                        'An employee has used or reserved %d %s sessions, but %s allows %d per employee this period.',
                        $highestUsage,
                        $type->label(),
                        $targetPlan->name,
                        $targetAllocation->limit,
                    );
                }

                continue;
            }

            if ($totalUsage > $targetAllocation->limit) {
                $violations[] = sprintf(
                    'You have used or reserved %d %s sessions, but %s allows %d this period.',
                    $totalUsage,
                    $type->label(),
                    $targetPlan->name,
                    $targetAllocation->limit,
                );
            }
        }

        return new PlanChangeEligibility(
            occupiedCapacity: $occupiedCapacity,
            targetCapacityCount: $targetCapacity,
            violations: array_values(array_unique($violations)),
        );
    }

    /** @return Collection<int, int> */
    private function usageByBeneficiary(
        Workspace $workspace,
        ConsultationAllocation $allocation,
    ): Collection {
        $beneficiaries = $workspace->beneficiaryEnrollments()
            ->consumingCapacity()
            ->get(['id', 'beneficiary_id']);
        $byPatient = $beneficiaries
            ->whereNotNull('beneficiary_id')
            ->keyBy('beneficiary_id');
        $usage = $beneficiaries
            ->mapWithKeys(static fn ($beneficiary): array => [(int) $beneficiary->getKey() => 0]);

        $ledger = Consultation::query()
            ->whereBelongsTo($workspace)
            ->where('consultation_type', $allocation->type)
            ->whereIn('status', [
                ConsultationReservationStatus::Reserved,
                ConsultationReservationStatus::Confirmed,
            ])
            ->whereBetween('reserved_at', [$allocation->periodStart, $allocation->periodEnd])
            ->get(['workspace_beneficiary_id', 'appointment_id']);
        $appointmentIds = $ledger->pluck('appointment_id')->filter()->map(fn ($id): int => (int) $id)->values();
        $appointmentStatuses = $appointmentIds->isEmpty()
            ? collect()
            : Appointment::query()
                ->whereKey($appointmentIds->all())
                ->pluck('status', 'appointment_id');

        foreach ($ledger as $reservation) {
            $status = $reservation->appointment_id === null
                ? null
                : $appointmentStatuses->get($reservation->appointment_id);

            if ($status === AppointmentStatus::Cancelled || $status === AppointmentStatus::Cancelled->value) {
                continue;
            }

            $beneficiaryId = (int) $reservation->workspace_beneficiary_id;
            $usage->put($beneficiaryId, (int) $usage->get($beneficiaryId, 0) + 1);
        }

        $legacyAppointments = Appointment::query()
            ->select(['appointment_id', 'patient_id', 'doctor_id', 'status', 'created_at'])
            ->with('doctor:id,provider_type')
            ->sponsoredBy($workspace)
            ->whereIn('status', [AppointmentStatus::Upcoming, AppointmentStatus::Completed])
            ->whereBetween('created_at', [$allocation->periodStart, $allocation->periodEnd])
            ->when($appointmentIds->isNotEmpty(), fn ($query) => $query->whereKeyNot($appointmentIds->all()))
            ->get();

        foreach ($legacyAppointments as $appointment) {
            if ($this->consultationTypes->resolve($appointment->doctor?->provider_type) !== $allocation->type) {
                continue;
            }

            $beneficiary = $byPatient->get($appointment->patient_id);

            if ($beneficiary === null) {
                continue;
            }

            $beneficiaryId = (int) $beneficiary->getKey();
            $usage->put($beneficiaryId, (int) $usage->get($beneficiaryId, 0) + 1);
        }

        return $usage;
    }
}
