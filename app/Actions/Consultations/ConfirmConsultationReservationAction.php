<?php

namespace App\Actions\Consultations;

use App\Enums\Appointments\AppointmentStatus;
use App\Enums\CampaignUsageBenefit;
use App\Enums\CampaignUsageSource;
use App\Enums\Consultations\ConsultationReservationStatus;
use App\Models\Campaign;
use App\Models\CampaignUsageEntry;
use App\Models\Consultations\Appointment;
use App\Models\Consultations\Consultation;
use App\Models\WorkspaceBeneficiary;
use App\Support\Payments\PaymentReferenceGenerator;
use App\ValueObjects\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ConfirmConsultationReservationAction
{
    public function __construct(private readonly PaymentReferenceGenerator $references) {}

    public function execute(Appointment $appointment): Consultation
    {
        $appointment = Appointment::query()
            ->select(['appointment_id', 'patient_id', 'doctor_id', 'sponsor_id', 'status'])
            ->find($appointment->getKey());

        if (! $appointment instanceof Appointment) {
            throw ValidationException::withMessages([
                'appointment_id' => 'The selected HealthBubba appointment does not exist.',
            ]);
        }

        $reservation = Consultation::query()
            ->where('appointment_id', $appointment->getKey())
            ->first();

        if (! $reservation instanceof Consultation) {
            throw ValidationException::withMessages([
                'appointment_id' => 'No consultation reservation exists for this appointment.',
            ]);
        }

        $this->validateAppointment($reservation, $appointment);

        $workspaceId = (string) $reservation->workspace_id;
        $assigned = Appointment::query()
            ->whereKey($appointment->getKey())
            ->where(function ($query) use ($workspaceId): void {
                $query->whereNull('sponsor_id')->orWhere('sponsor_id', $workspaceId);
            })
            ->update(['sponsor_id' => $workspaceId]);
        $appointment->refresh();

        if ($assigned === 0 && $appointment->sponsor_id !== $workspaceId) {
            throw ValidationException::withMessages([
                'appointment_id' => 'This appointment is already assigned to another sponsor.',
            ]);
        }

        return DB::transaction(function () use ($reservation): Consultation {
            $locked = Consultation::query()
                ->whereKey($reservation->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status === ConsultationReservationStatus::Cancelled) {
                throw ValidationException::withMessages([
                    'appointment_id' => 'This consultation reservation has been cancelled.',
                ]);
            }

            if ($locked->status === ConsultationReservationStatus::Reserved) {
                $locked->update([
                    'status' => ConsultationReservationStatus::Confirmed,
                    'confirmed_at' => now(),
                ]);
            }

            $this->recordCampaignUsage($locked);

            return $locked->refresh();
        });
    }

    private function recordCampaignUsage(Consultation $consultation): void
    {
        $membership = WorkspaceBeneficiary::query()->find($consultation->workspace_beneficiary_id);

        if (! $membership instanceof WorkspaceBeneficiary
            || $membership->relatable_type !== (new Campaign)->getMorphClass()) {
            return;
        }

        $campaign = Campaign::query()->find($membership->relatable_id);

        if (! $campaign instanceof Campaign) {
            return;
        }

        $benefit = CampaignUsageBenefit::from($consultation->consultation_type->value);
        $fee = Money::fromMajor(
            $benefit === CampaignUsageBenefit::GeneralPractitioner
                ? ($campaign->gp_fee ?? '0.00')
                : ($campaign->specialist_fee ?? '0.00'),
            $campaign->currency,
        );

        CampaignUsageEntry::query()->firstOrCreate(
            ['source_reference' => "consultation:{$consultation->getKey()}"],
            [
                'campaign_id' => $campaign->getKey(),
                'workspace_id' => $campaign->workspace_id,
                'workspace_beneficiary_id' => $membership->getKey(),
                'benefit' => $benefit,
                'quantity' => 1,
                'unit_amount' => $fee->toMajorAmount(),
                'total_amount' => $fee->toMajorAmount(),
                'currency' => $fee->currency,
                'source' => CampaignUsageSource::Provider,
                'reference' => $this->references->generateCampaignUsage(),
                'occurred_at' => now(),
            ],
        );
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

        if ($appointment->status !== AppointmentStatus::Completed) {
            throw ValidationException::withMessages([
                'appointment_id' => 'Consultation usage can only be recorded for a completed appointment.',
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
