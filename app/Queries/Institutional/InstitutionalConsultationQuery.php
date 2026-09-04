<?php

namespace App\Queries\Institutional;

use App\DTOs\Institutional\InstitutionalConsultationRow;
use App\Enums\Appointments\AppointmentStatus;
use App\Models\Campaign;
use App\Models\Consultations\Appointment;
use App\Models\Consultations\Consultation;
use App\Models\Workspace;
use App\Models\WorkspaceBeneficiary;
use App\Services\Consultations\ConsultationTypeResolver;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class InstitutionalConsultationQuery
{
    public function __construct(private ConsultationTypeResolver $types) {}

    /** @return LengthAwarePaginator<int, InstitutionalConsultationRow> */
    public function paginate(Workspace $workspace, ?string $campaignSlug): LengthAwarePaginator
    {
        $enrollmentIds = WorkspaceBeneficiary::query()
            ->whereBelongsTo($workspace)
            ->where('relatable_type', (new Campaign)->getMorphClass())
            ->whereNotNull('beneficiary_id')
            ->when($campaignSlug !== null && $campaignSlug !== '', function ($query) use ($workspace, $campaignSlug): void {
                $query->whereIn('relatable_id', Campaign::query()
                    ->whereBelongsTo($workspace)
                    ->where('slug', $campaignSlug)
                    ->select('id'));
            })
            ->pluck('id');

        if ($enrollmentIds->isEmpty()) {
            return $this->emptyPaginator();
        }

        $appointmentIds = Consultation::query()
            ->whereBelongsTo($workspace)
            ->whereIn('workspace_beneficiary_id', $enrollmentIds->all())
            ->whereNotNull('appointment_id')
            ->pluck('appointment_id')
            ->map(static fn (mixed $appointmentId): int => (int) $appointmentId)
            ->all();

        if ($appointmentIds === []) {
            return $this->emptyPaginator();
        }

        $appointmentQuery = Appointment::query()
            ->select(['appointment_id', 'patient_id', 'doctor_id', 'sponsor_id', 'date', 'time', 'status', 'created_at'])
            ->with(['patient:id,first_name,last_name', 'doctor:id,provider_type'])
            ->sponsoredBy($workspace)
            ->whereIn('appointment_id', $appointmentIds);

        $appointments = $appointmentQuery
            ->latest('date')
            ->latest('appointment_id')
            ->paginate(20)
            ->withQueryString();
        $appointmentIds = $appointments->getCollection()->pluck('appointment_id')->all();
        $snapshotsByAppointment = Consultation::query()
            ->whereBelongsTo($workspace)
            ->whereIn('appointment_id', $appointmentIds)
            ->with('workspaceBeneficiary.relatable')
            ->get()
            ->keyBy('appointment_id')
            ->all();

        return $appointments->through(function (Appointment $appointment) use ($snapshotsByAppointment): InstitutionalConsultationRow {
            $snapshot = $snapshotsByAppointment[$appointment->getKey()] ?? null;
            $campaign = $snapshot instanceof Consultation
                ? $snapshot->workspaceBeneficiary->relatable
                : null;
            $type = $snapshot instanceof Consultation
                ? $snapshot->consultation_type
                : $this->types->resolve($appointment->doctor?->provider_type);

            return new InstitutionalConsultationRow(
                id: (int) $appointment->getKey(),
                date: $appointment->date?->toDateString() ?? $appointment->created_at?->toDateString(),
                beneficiary: trim("{$appointment->patient?->first_name} {$appointment->patient?->last_name}"),
                campaign: $campaign instanceof Campaign ? [
                    'name' => $campaign->name,
                    'slug' => $campaign->slug,
                ] : null,
                type: $type->value,
                typeLabel: $type->label(),
                status: match ($appointment->status) {
                    AppointmentStatus::Upcoming => 'scheduled',
                    AppointmentStatus::Completed => 'completed',
                    AppointmentStatus::Cancelled => 'cancelled',
                },
                statusLabel: match ($appointment->status) {
                    AppointmentStatus::Upcoming => 'Scheduled',
                    AppointmentStatus::Completed => 'Completed',
                    AppointmentStatus::Cancelled => 'Cancelled',
                },
                paymentSource: 'sponsor_coverage',
                paymentSourceLabel: 'Sponsor coverage',
            );
        });
    }

    /** @return LengthAwarePaginator<int, InstitutionalConsultationRow> */
    private function emptyPaginator(): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            items: collect(), total: 0, perPage: 20,
            currentPage: max(1, (int) request()->query('page', 1)),
            options: ['path' => request()->url(), 'query' => request()->query()],
        );
    }
}
