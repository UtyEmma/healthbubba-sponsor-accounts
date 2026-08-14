<?php

namespace App\Queries\Consultations;

use App\DTOs\Consultations\ConsultationViewData;
use App\Enums\AccountTypes;
use App\Enums\Consultations\ConsultationAllocationScope;
use App\Models\Consultations\Appointment;
use App\Models\Consultations\Consultation;
use App\Models\Subscription;
use App\Models\Workspace;
use App\Services\Consultations\ConsultationCoverageService;
use App\Services\Consultations\ConsultationTypeResolver;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\LengthAwarePaginator;
use Throwable;

final readonly class WorkspaceConsultationQuery
{
    public function __construct(
        private ConsultationCoverageService $coverage,
        private ConsultationTypeResolver $types,
    ) {}

    /** @return LengthAwarePaginator<int, ConsultationViewData> */
    public function paginate(Workspace $workspace): LengthAwarePaginator
    {
        $appointments = Appointment::query()
            ->select([
                'appointment_id',
                'patient_id',
                'doctor_id',
                'sponsor_id',
                'date',
                'time',
                'status',
                'created_at',
            ])
            ->with([
                'patient:id,first_name,last_name,email,phone',
                'doctor:id,first_name,last_name,provider_type',
            ])
            ->sponsoredBy($workspace)
            ->latest('created_at')
            ->latest('appointment_id')
            ->paginate(20)
            ->withQueryString();
        $appointmentIds = $appointments->getCollection()
            ->pluck('appointment_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();
        $snapshots = Consultation::query()
            ->whereBelongsTo($workspace)
            ->whereIn('appointment_id', $appointmentIds)
            ->get([
                'appointment_id',
                'consultation_type',
                'feature_slug',
                'plan_name',
                'allocation_scope',
            ])
            ->mapWithKeys(static fn (Consultation $consultation): array => [
                $consultation->appointment_id => $consultation,
            ])
            ->all();
        $subscription = $this->coverage->activeSubscription($workspace);

        return $appointments->through(function (Appointment $appointment) use ($snapshots, $subscription, $workspace): ConsultationViewData {
            $snapshot = $snapshots[$appointment->getKey()] ?? null;
            $type = $snapshot instanceof Consultation
                ? $snapshot->consultation_type
                : $this->types->resolve($appointment->doctor?->provider_type);
            $scope = $snapshot instanceof Consultation
                ? $snapshot->allocation_scope
                : ($workspace->type === AccountTypes::BUSINESS
                    ? ConsultationAllocationScope::PerEmployee
                    : ConsultationAllocationScope::Shared);
            $featureSlug = $snapshot instanceof Consultation
                ? $snapshot->feature_slug
                : $this->types->feature($workspace->type, $type)->value;

            return new ConsultationViewData(
                appointment: $appointment,
                type: $type,
                featureSlug: $featureSlug,
                planName: $snapshot instanceof Consultation
                    ? $snapshot->plan_name
                    : ($subscription instanceof Subscription ? $subscription->plan->name : null),
                scope: $scope,
                scheduledAt: $this->scheduledAt($appointment),
            );
        });
    }

    private function scheduledAt(Appointment $appointment): ?CarbonImmutable
    {
        if ($appointment->date === null) {
            return null;
        }

        try {
            return CarbonImmutable::parse(
                $appointment->date->format('Y-m-d').' '.($appointment->time ?? '00:00:00'),
                config('app.timezone'),
            );
        } catch (Throwable) {
            return null;
        }
    }
}
