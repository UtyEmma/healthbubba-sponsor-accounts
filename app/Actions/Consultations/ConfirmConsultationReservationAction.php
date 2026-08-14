<?php

namespace App\Actions\Consultations;

use App\Enums\Appointments\AppointmentStatus;
use App\Enums\Consultations\ConsultationReservationStatus;
use App\Models\Consultations\Appointment;
use App\Models\Consultations\Consultation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ConfirmConsultationReservationAction
{
    public function execute(Consultation $reservation, int $appointmentId): Consultation
    {
        $appointment = Appointment::query()
            ->select(['appointment_id', 'patient_id', 'doctor_id', 'sponsor_id', 'status'])
            ->find($appointmentId);

        if (! $appointment instanceof Appointment) {
            throw ValidationException::withMessages([
                'appointment_id' => 'The selected HealthBubba appointment does not exist.',
            ]);
        }

        $this->validateAppointment($reservation, $appointment);

        $attachedReservation = DB::transaction(function () use ($reservation, $appointment): Consultation {
            $locked = Consultation::query()
                ->whereKey($reservation->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status === ConsultationReservationStatus::Cancelled) {
                throw ValidationException::withMessages([
                    'reservation' => 'This consultation reservation has been cancelled.',
                ]);
            }

            if ($locked->appointment_id !== null && $locked->appointment_id !== $appointment->getKey()) {
                throw ValidationException::withMessages([
                    'appointment_id' => 'This reservation is already attached to another appointment.',
                ]);
            }

            $usedByAnotherReservation = Consultation::query()
                ->where('appointment_id', $appointment->getKey())
                ->whereKeyNot($locked->getKey())
                ->exists();

            if ($usedByAnotherReservation) {
                throw ValidationException::withMessages([
                    'appointment_id' => 'This appointment is already attached to another reservation.',
                ]);
            }

            if ($locked->appointment_id === null) {
                $locked->update(['appointment_id' => $appointment->getKey()]);
            }

            return $locked->refresh();
        });

        $workspaceId = (string) $attachedReservation->workspace_id;
        $assigned = Appointment::query()
            ->whereKey($appointment->getKey())
            ->where(function ($query) use ($workspaceId): void {
                $query->whereNull('sponsor_id')->orWhere('sponsor_id', $workspaceId);
            })
            ->update(['sponsor_id' => $workspaceId]);
        $appointment->refresh();

        if ($assigned === 0 && $appointment->sponsor_id !== $workspaceId) {
            DB::transaction(function () use ($attachedReservation, $appointment): void {
                Consultation::query()
                    ->whereKey($attachedReservation->getKey())
                    ->where('status', ConsultationReservationStatus::Reserved)
                    ->where('appointment_id', $appointment->getKey())
                    ->update(['appointment_id' => null]);
            });

            throw ValidationException::withMessages([
                'appointment_id' => 'This appointment is already assigned to another sponsor.',
            ]);
        }

        return DB::transaction(function () use ($attachedReservation): Consultation {
            $locked = Consultation::query()
                ->whereKey($attachedReservation->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status === ConsultationReservationStatus::Reserved) {
                $locked->update([
                    'status' => ConsultationReservationStatus::Confirmed,
                    'confirmed_at' => now(),
                ]);
            }

            return $locked->refresh();
        });
    }

    private function validateAppointment(Consultation $reservation, Appointment $appointment): void
    {
        if ($appointment->patient_id !== $reservation->beneficiary_id) {
            throw ValidationException::withMessages([
                'appointment_id' => 'The appointment patient does not match this reservation.',
            ]);
        }

        if ($appointment->doctor_id !== $reservation->doctor_id) {
            throw ValidationException::withMessages([
                'appointment_id' => 'The appointment doctor does not match this reservation.',
            ]);
        }

        if ($appointment->status === AppointmentStatus::Cancelled) {
            throw ValidationException::withMessages([
                'appointment_id' => 'A cancelled appointment cannot be sponsored.',
            ]);
        }

        if ($appointment->sponsor_id !== null
            && $appointment->sponsor_id !== (string) $reservation->workspace_id) {
            throw ValidationException::withMessages([
                'appointment_id' => 'This appointment is already assigned to another sponsor.',
            ]);
        }
    }
}
