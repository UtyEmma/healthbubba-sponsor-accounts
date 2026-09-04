<?php

namespace App\Actions\Consultations;

use App\DTOs\Consultations\RecordConsultationUsageData;
use App\Enums\Appointments\AppointmentStatus;
use App\Models\Consultations\Appointment;
use App\Models\Consultations\Consultation;
use Illuminate\Validation\ValidationException;

final readonly class RecordConsultationUsageAction
{
    public function __construct(
        private ConfirmConsultationReservationAction $confirmReservation,
    ) {}

    public function execute(RecordConsultationUsageData $data): Consultation
    {
        $appointment = $this->appointment($data->appointmentId);
        $this->validateAppointment($appointment);

        return $this->confirmReservation
            ->execute($appointment)
            ->load('workspace');
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

    private function validateAppointment(Appointment $appointment): void
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
    }
}
