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
    public function execute(Consultation $reservation): Consultation
    {
        return DB::transaction(function () use ($reservation): Consultation {
            $locked = Consultation::query()
                ->whereKey($reservation->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status === ConsultationReservationStatus::Cancelled) {
                return $locked;
            }

            if ($locked->appointment_id !== null) {
                $appointment = Appointment::query()
                    ->select(['appointment_id', 'sponsor_id', 'status'])
                    ->find($locked->appointment_id);

                if ($appointment instanceof Appointment
                    && $appointment->sponsor_id === (string) $locked->workspace_id
                    && $appointment->status !== AppointmentStatus::Cancelled) {
                    throw ValidationException::withMessages([
                        'reservation' => 'Cancel the sponsored HealthBubba appointment before releasing its allocation.',
                    ]);
                }
            }

            $locked->update([
                'status' => ConsultationReservationStatus::Cancelled,
                'cancelled_at' => now(),
            ]);

            return $locked->refresh();
        });
    }
}
