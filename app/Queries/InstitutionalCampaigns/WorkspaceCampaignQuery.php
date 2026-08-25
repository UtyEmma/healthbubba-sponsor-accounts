<?php

namespace App\Queries\InstitutionalCampaigns;

use App\DTOs\Consultations\ConsultationViewData;
use App\Enums\Appointments\AppointmentStatus;
use App\Enums\WorkspaceBeneficiaries\WorkspaceBeneficiaryStatus;
use App\Models\Campaign;
use App\Models\Consultations\Appointment;
use App\Models\Consultations\Consultation;
use App\Models\Workspace;
use App\Models\WorkspaceBeneficiary;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator as PaginationLengthAwarePaginator;

final readonly class WorkspaceCampaignQuery
{
    /** @return LengthAwarePaginator<int, Campaign> */
    public function paginate(Workspace $workspace): LengthAwarePaginator
    {
        return Campaign::query()
            ->whereBelongsTo($workspace)
            ->withCount([
                'beneficiaries',
                'activeBeneficiaries',
                'beneficiaries as capacity_used' => static function (Builder $query): void {
                    self::whereCurrentlyEnrolled($query);
                },
            ])
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();
    }

    /** @return LengthAwarePaginator<int, WorkspaceBeneficiary> */
    public function paginateBeneficiaries(Campaign $campaign): LengthAwarePaginator
    {
        return $campaign->beneficiaries()
            ->with('relatable')
            ->latest('id')
            ->paginate(
                perPage: 10,
                pageName: 'beneficiaries_page',
            )
            ->withQueryString();
    }

    /** @return LengthAwarePaginator<int, ConsultationViewData> */
    public function paginateConsultations(Campaign $campaign): LengthAwarePaginator
    {
        $beneficiaryIds = $campaign->beneficiaries()
            ->whereNotNull('beneficiary_id')
            ->pluck('id');

        if ($beneficiaryIds->isEmpty()) {
            return new PaginationLengthAwarePaginator(
                items: collect(),
                total: 0,
                perPage: 20,
                currentPage: (int) request()->query('consultations_page', 1),
                options: ['path' => request()->url(), 'query' => request()->query()],
            );
        }

        $snapshots = Consultation::query()
            ->whereIn('workspace_beneficiary_id', $beneficiaryIds->all())
            ->orderByDesc('id')
            ->paginate(
                perPage: 20,
                pageName: 'consultations_page',
            )
            ->withQueryString();

        $appointmentIds = $snapshots->getCollection()
            ->pluck('appointment_id')
            ->filter()
            ->values()
            ->all();

        $appointments = empty($appointmentIds)
            ? collect()
            : Appointment::query()
                ->select([
                    'appointment_id',
                    'patient_id',
                    'doctor_id',
                    'date',
                    'time',
                    'status',
                    'created_at',
                ])
                ->with([
                    'patient:id,first_name,last_name,email,phone',
                    'doctor:id,first_name,last_name,provider_type',
                ])
                ->whereKey($appointmentIds)
                ->get()
                ->keyBy('appointment_id');

        return $snapshots->through(function (Consultation $consultation) use ($appointments): ConsultationViewData {
            $appointment = $appointments->get($consultation->appointment_id);

            return new ConsultationViewData(
                appointment: $appointment ?? $this->buildPlaceholderAppointment($consultation),
                type: $consultation->consultation_type,
                featureSlug: $consultation->feature_slug,
                planName: $consultation->plan_name,
                scope: $consultation->allocation_scope,
                scheduledAt: $appointment?->date
                    ? $appointment->date->timezone(config('app.timezone'))
                    : null,
            );
        });
    }

    private function buildPlaceholderAppointment(Consultation $consultation): Appointment
    {
        $appointment = new Appointment([
            'appointment_id' => $consultation->appointment_id ?? 0,
            'patient_id' => $consultation->beneficiary_id,
            'doctor_id' => $consultation->doctor_id,
            'status' => $consultation->status->value === 'confirmed'
                ? AppointmentStatus::Upcoming
                : AppointmentStatus::Upcoming,
            'created_at' => $consultation->reserved_at,
        ]);

        return $appointment;
    }

    public function prepareForDisplay(Campaign $campaign): Campaign
    {
        return $campaign->loadCount([
            'beneficiaries',
            'activeBeneficiaries',
            'beneficiaries as capacity_used' => static function (Builder $query): void {
                self::whereCurrentlyEnrolled($query);
            },
        ]);
    }

    /** @param Builder<WorkspaceBeneficiary> $query */
    private static function whereCurrentlyEnrolled(Builder $query): void
    {
        $query->where(function (Builder $query): void {
            $query->whereIn('status', [
                WorkspaceBeneficiaryStatus::Active,
                WorkspaceBeneficiaryStatus::Suspended,
            ])->orWhere(function (Builder $query): void {
                $query->where('status', WorkspaceBeneficiaryStatus::Pending)
                    ->where('expires_at', '>', now());
            });
        });
    }
}
