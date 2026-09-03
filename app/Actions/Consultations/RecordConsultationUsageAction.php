<?php

namespace App\Actions\Consultations;

use App\DTOs\Consultations\ConsultationEligibilityData;
use App\DTOs\Consultations\RecordConsultationUsageData;
use App\Enums\Appointments\AppointmentStatus;
use App\Enums\Consultations\ConsultationReservationStatus;
use App\Models\Consultations\Appointment;
use App\Models\Consultations\Consultation;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

final readonly class RecordConsultationUsageAction
{
    public function __construct(
        private ReserveConsultationAction $reserveConsultation,
        private ConfirmConsultationReservationAction $confirmReservation,
        private CancelConsultationReservationAction $cancelReservation,
    ) {}

    public function execute(RecordConsultationUsageData $data): Consultation
    {
        $appointment = $this->appointment($data->appointmentId);
        $this->validateAppointment($appointment, $data->sponsorId);

        [$reservation, $created] = DB::transaction(function () use ($appointment, $data): array {
            $workspace = Workspace::query()
                ->whereKey($data->sponsorId)
                ->lockForUpdate()
                ->first();

            if (! $workspace instanceof Workspace) {
                throw ValidationException::withMessages([
                    'sponsor_id' => 'The selected sponsor does not exist.',
                ]);
            }

            $existing = Consultation::query()
                ->where('appointment_id', $appointment->getKey())
                ->lockForUpdate()
                ->first();

            if ($existing instanceof Consultation) {
                if ($existing->workspace_id !== $workspace->getKey()) {
                    throw ValidationException::withMessages([
                        'sponsor_id' => 'This appointment is already assigned to another sponsor.',
                    ]);
                }

                if ($existing->status === ConsultationReservationStatus::Cancelled) {
                    throw ValidationException::withMessages([
                        'appointment_id' => 'The sponsorship usage for this appointment has been cancelled.',
                    ]);
                }

                return [$existing, false];
            }

            $eligibility = $this->reserveConsultation->execute(
                new ConsultationEligibilityData(
                    sponsorId: (int) $workspace->getKey(),
                    patientId: $appointment->patient_id,
                    doctorId: (int) $appointment->doctor_id,
                ),
            );

            if (! $eligibility->available || ! $eligibility->reservation instanceof Consultation) {
                throw ValidationException::withMessages([
                    'sponsor_id' => $this->unavailableMessage($eligibility->reason),
                ]);
            }

            return [$eligibility->reservation, $eligibility->reservation->wasRecentlyCreated];
        });

        try {
            return $this->confirmReservation
                ->execute($reservation, $data->appointmentId)
                ->load('workspace');
        } catch (Throwable $exception) {
            if ($created) {
                try {
                    $reservation->refresh();

                    if ($reservation->status === ConsultationReservationStatus::Reserved
                        && $reservation->appointment_id === null) {
                        $this->cancelReservation->execute($reservation);
                    }
                } catch (Throwable $cleanupException) {
                    report($cleanupException);
                }
            }

            throw $exception;
        }
    }

    private function appointment(int $appointmentId): Appointment
    {
        $appointment = Appointment::query()
            ->select(['appointment_id', 'patient_id', 'doctor_id', 'sponsor_id', 'status'])
            ->find($appointmentId);

        if (! $appointment instanceof Appointment) {
            throw ValidationException::withMessages([
                'appointment_id' => 'The selected HealthBubba appointment does not exist.',
            ]);
        }

        return $appointment;
    }

    private function validateAppointment(Appointment $appointment, int $sponsorId): void
    {
        if ($appointment->status !== AppointmentStatus::Completed) {
            throw ValidationException::withMessages([
                'appointment_id' => 'Consultation usage can only be recorded for a completed appointment.',
            ]);
        }

        if ($appointment->doctor_id === null) {
            throw ValidationException::withMessages([
                'appointment_id' => 'The appointment must have a doctor before usage can be recorded.',
            ]);
        }

        if ($appointment->sponsor_id !== null
            && $appointment->sponsor_id !== (string) $sponsorId) {
            throw ValidationException::withMessages([
                'sponsor_id' => 'This appointment is already assigned to another sponsor.',
            ]);
        }
    }

    private function unavailableMessage(?string $reason): string
    {
        return match ($reason) {
            'workspace_not_found' => 'The selected sponsor does not exist.',
            'doctor_not_found' => 'The appointment doctor does not exist.',
            'no_active_subscription' => 'The sponsor does not have an active subscription.',
            'patient_not_eligible' => 'The patient is not actively covered by this sponsor.',
            'feature_unavailable' => 'This consultation type is not available under the sponsorship.',
            'allocation_exhausted' => 'The sponsor has no remaining allocation for this consultation type.',
            default => 'This consultation cannot be covered by the selected sponsor.',
        };
    }
}
