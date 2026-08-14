<?php

namespace App\Actions\Consultations;

use App\DTOs\Consultations\ConsultationAllocation;
use App\DTOs\Consultations\ConsultationEligibilityData;
use App\DTOs\Consultations\ConsultationEligibilityResult;
use App\Enums\Consultations\ConsultationReservationStatus;
use App\Enums\WorkspaceBeneficiaries\WorkspaceBeneficiaryStatus;
use App\Models\Consultations\Consultation;
use App\Models\Doctor;
use App\Models\Subscription;
use App\Models\Workspace;
use App\Models\WorkspaceBeneficiary;
use App\Services\Consultations\ConsultationCoverageService;
use App\Services\Consultations\ConsultationTypeResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class ReserveConsultationAction
{
    public function __construct(
        private ConsultationCoverageService $coverage,
        private ConsultationTypeResolver $types,
    ) {}

    public function execute(ConsultationEligibilityData $data): ConsultationEligibilityResult
    {
        $workspace = Workspace::query()->find($data->sponsorId);

        if (! $workspace instanceof Workspace) {
            return ConsultationEligibilityResult::unavailable('workspace_not_found');
        }

        $doctor = Doctor::query()
            ->select(['id', 'provider_type'])
            ->find($data->doctorId);

        if (! $doctor instanceof Doctor) {
            return ConsultationEligibilityResult::unavailable('doctor_not_found');
        }

        $type = $this->types->resolve($doctor->provider_type);

        return DB::transaction(function () use ($data, $workspace, $type): ConsultationEligibilityResult {
            $subscription = $this->coverage->activeSubscription($workspace, lock: true);

            if (! $subscription instanceof Subscription) {
                return ConsultationEligibilityResult::unavailable('no_active_subscription', $type);
            }

            $workspaceBeneficiary = WorkspaceBeneficiary::query()
                ->whereBelongsTo($workspace)
                ->where('beneficiary_id', $data->patientId)
                ->where('status', WorkspaceBeneficiaryStatus::Active)
                ->lockForUpdate()
                ->first();

            if (! $workspaceBeneficiary instanceof WorkspaceBeneficiary) {
                return ConsultationEligibilityResult::unavailable('patient_not_eligible', $type);
            }

            $existing = Consultation::query()
                ->whereBelongsTo($workspace)
                ->whereBelongsTo($workspaceBeneficiary)
                ->where('beneficiary_id', $data->patientId)
                ->where('doctor_id', $data->doctorId)
                ->where('consultation_type', $type)
                ->where('status', ConsultationReservationStatus::Reserved)
                ->latest('id')
                ->first();

            if ($existing instanceof Consultation) {
                return ConsultationEligibilityResult::available($existing);
            }

            $allocation = $this->coverage->allocation(
                $workspace,
                $subscription,
                $type,
                $workspaceBeneficiary,
            );

            if (! $allocation instanceof ConsultationAllocation) {
                return ConsultationEligibilityResult::unavailable('feature_unavailable', $type);
            }

            $usage = $this->coverage->usage($workspace, $allocation);

            if ($allocation->limit !== null && $usage->total() >= $allocation->limit) {
                return ConsultationEligibilityResult::unavailable('allocation_exhausted', $type);
            }

            $reservation = Consultation::query()->create([
                'public_id' => (string) Str::ulid(),
                'workspace_id' => $workspace->getKey(),
                'workspace_beneficiary_id' => $workspaceBeneficiary->getKey(),
                'plan_id' => $allocation->planId,
                'beneficiary_id' => $data->patientId,
                'doctor_id' => $data->doctorId,
                'consultation_type' => $allocation->type,
                'feature_slug' => $allocation->featureSlug,
                'status' => ConsultationReservationStatus::Reserved,
                'allocation_scope' => $allocation->scope,
                'plan_name' => $allocation->planName,
                'allocation_limit' => $allocation->limit,
                'allocation_period_start' => $allocation->periodStart,
                'allocation_period_end' => $allocation->periodEnd,
                'reserved_at' => now(),
            ]);

            return ConsultationEligibilityResult::available($reservation);
        });
    }
}
