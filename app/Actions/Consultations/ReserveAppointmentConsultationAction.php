<?php

namespace App\Actions\Consultations;

use App\DTOs\Consultations\ConsultationEligibilityData;
use App\DTOs\Consultations\ReserveConsultationData;
use App\Enums\Appointments\AppointmentStatus;
use App\Enums\Consultations\ConsultationReservationStatus;
use App\Models\Campaign;
use App\Models\Consultations\Appointment;
use App\Models\Consultations\Consultation;
use App\Models\WorkspaceBeneficiary;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

final readonly class ReserveAppointmentConsultationAction
{
    public function __construct(
        private ReserveConsultationAction $reserveConsultation,
        private CancelConsultationReservationAction $cancelReservation,
    ) {}

    public function execute(ReserveConsultationData $data): Consultation
    {
        return DB::connection((new Appointment)->getConnectionName())
            ->transaction(fn (): Consultation => $this->reserve($data));
    }

    private function reserve(ReserveConsultationData $data): Consultation
    {
        $appointment = $this->appointment($data->appointmentId);
        $this->validateAppointmentSponsor($appointment, $data->sponsorId);

        $existing = Consultation::query()
            ->where('appointment_id', $appointment->getKey())
            ->first();

        if ($existing instanceof Consultation) {
            $this->validateExistingReservation(
                $existing,
                $data->sponsorId,
                $data->campaignId,
            );
            $this->assignSponsor($appointment, $data->sponsorId);

            return $existing->refresh();
        }

        if ($appointment->status !== AppointmentStatus::Upcoming) {
            throw ValidationException::withMessages([
                'appointment_id' => 'Only an upcoming appointment can be reserved.',
            ]);
        }

        if ($appointment->doctor_id === null) {
            throw ValidationException::withMessages([
                'appointment_id' => 'The appointment must have a doctor before it can be reserved.',
            ]);
        }

        $eligibility = $this->reserveConsultation->execute(
            new ConsultationEligibilityData(
                sponsorId: $data->sponsorId,
                appointmentId: (int) $appointment->getKey(),
                patientId: $appointment->patient_id,
                doctorId: $appointment->doctor_id,
                campaignId: $data->campaignId,
            ),
        );

        if (! $eligibility->available || ! $eligibility->reservation instanceof Consultation) {
            throw ValidationException::withMessages([
                $this->unavailableField($eligibility->reason) => $this->unavailableMessage($eligibility->reason),
            ]);
        }

        try {
            $this->assignSponsor($appointment, $data->sponsorId);
        } catch (Throwable $exception) {
            try {
                $this->cancelReservation->execute($appointment);
            } catch (Throwable $cleanupException) {
                report($cleanupException);
            }

            throw $exception;
        }

        return $eligibility->reservation->refresh();
    }

    private function appointment(int $appointmentId): Appointment
    {
        $appointment = Appointment::query()
            ->select(['appointment_id', 'patient_id', 'doctor_id', 'sponsor_id', 'status'])
            ->lockForUpdate()
            ->find($appointmentId);

        if (! $appointment instanceof Appointment) {
            throw ValidationException::withMessages([
                'appointment_id' => 'The selected HealthBubba appointment does not exist.',
            ]);
        }

        return $appointment;
    }

    private function validateAppointmentSponsor(Appointment $appointment, int $sponsorId): void
    {
        if ($appointment->sponsor_id !== null
            && $appointment->sponsor_id !== (string) $sponsorId) {
            throw ValidationException::withMessages([
                'sponsor_id' => 'This appointment is already assigned to another sponsor.',
            ]);
        }
    }

    private function validateExistingReservation(
        Consultation $reservation,
        int $sponsorId,
        ?int $campaignId,
    ): void {
        if ($reservation->workspace_id !== $sponsorId) {
            throw ValidationException::withMessages([
                'sponsor_id' => 'This appointment is already reserved by another sponsor.',
            ]);
        }

        if ($reservation->status === ConsultationReservationStatus::Cancelled) {
            throw ValidationException::withMessages([
                'appointment_id' => 'The reservation for this appointment has been cancelled.',
            ]);
        }

        if ($campaignId !== null) {
            $reservedCampaignId = WorkspaceBeneficiary::query()
                ->whereKey($reservation->workspace_beneficiary_id)
                ->where('relatable_type', (new Campaign)->getMorphClass())
                ->value('relatable_id');

            if ((int) $reservedCampaignId !== $campaignId) {
                throw ValidationException::withMessages([
                    'campaign_id' => 'This appointment is already reserved under another campaign.',
                ]);
            }
        }
    }

    private function assignSponsor(Appointment $appointment, int $sponsorId): void
    {
        $workspaceId = (string) $sponsorId;
        $assigned = Appointment::query()
            ->whereKey($appointment->getKey())
            ->where(function ($query) use ($workspaceId): void {
                $query->whereNull('sponsor_id')->orWhere('sponsor_id', $workspaceId);
            })
            ->update(['sponsor_id' => $workspaceId]);
        $appointment->refresh();

        if ($assigned === 0 && $appointment->sponsor_id !== $workspaceId) {
            throw ValidationException::withMessages([
                'sponsor_id' => 'This appointment is already assigned to another sponsor.',
            ]);
        }
    }

    private function unavailableMessage(?string $reason): string
    {
        return match ($reason) {
            'workspace_not_found' => 'The selected sponsor does not exist.',
            'campaign_required' => 'A campaign is required for an institutional sponsor.',
            'campaign_not_available' => 'The selected campaign does not belong to this sponsor.',
            'campaign_not_active' => 'The selected campaign is not active.',
            'campaign_not_applicable' => 'A campaign can only be selected for an institutional sponsor.',
            'patient_not_eligible_for_campaign' => 'The patient is not actively covered by the selected campaign.',
            'appointment_reserved_under_another_campaign' => 'This appointment is already reserved under another campaign.',
            'doctor_not_found' => 'The appointment doctor does not exist.',
            'no_active_subscription' => 'The sponsor does not have an active subscription.',
            'patient_not_eligible' => 'The patient is not actively covered by this sponsor.',
            'feature_unavailable' => 'This consultation type is not available under the sponsorship.',
            'allocation_exhausted' => 'The sponsor has no remaining allocation for this consultation type.',
            default => 'This consultation cannot be covered by the selected sponsor.',
        };
    }

    private function unavailableField(?string $reason): string
    {
        return in_array($reason, [
            'campaign_required',
            'campaign_not_available',
            'campaign_not_active',
            'campaign_not_applicable',
            'patient_not_eligible_for_campaign',
            'appointment_reserved_under_another_campaign',
        ], true) ? 'campaign_id' : 'sponsor_id';
    }
}
