<?php

namespace App\Actions\Consultations;

use App\Enums\Appointments\AppointmentStatus;
use App\Enums\Consultations\ConsultationReservationStatus;
use App\Models\Consultations\Appointment;
use App\Models\Consultations\Consultation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CancelConsultationReservationAction
{
    public function execute(Appointment $appointment): Consultation
    {
        $reservation = Consultation::query()
            ->where('appointment_id', $appointment->getKey())
            ->first();

        if (! $reservation instanceof Consultation) {
            throw ValidationException::withMessages([
                'appointment_id' => 'No consultation reservation exists for this appointment.',
            ]);
        }

        return DB::transaction(function () use ($appointment, $reservation): Consultation {
            $locked = Consultation::query()
                ->whereKey($reservation->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status === ConsultationReservationStatus::Cancelled) {
                return $locked;
            }

            if ($appointment->sponsor_id === (string) $locked->workspace_id
                && $appointment->status !== AppointmentStatus::Cancelled) {
                throw ValidationException::withMessages([
                    'reservation' => 'Cancel the sponsored HealthBubba appointment before releasing its allocation.',
                ]);
            }

            $locked->update([
                'status' => ConsultationReservationStatus::Cancelled,
                'cancelled_at' => now(),
            ]);

            return $locked->refresh();
        });
    }
}
