<?php

namespace App\Actions\Consultations;

use App\DTOs\Consultations\ConsultationAllocation;
use App\DTOs\Consultations\ConsultationEligibilityData;
use App\DTOs\Consultations\ConsultationEligibilityResult;
use App\Enums\AccountTypes;
use App\Enums\Consultations\ConsultationAllocationScope;
use App\Enums\Consultations\ConsultationReservationStatus;
use App\Enums\Consultations\ConsultationType;
use App\Enums\WorkspaceBeneficiaries\WorkspaceBeneficiaryStatus;
use App\Models\Campaign;
use App\Models\CampaignConsultationQuota;
use App\Models\Consultations\Consultation;
use App\Models\Doctor;
use App\Models\Subscription;
use App\Models\Workspace;
use App\Models\WorkspaceBeneficiary;
use App\Services\Consultations\ConsultationCoverageService;
use App\Services\Consultations\ConsultationTypeResolver;
use Carbon\CarbonImmutable;
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
            if ($workspace->type === AccountTypes::INSTITUTION) {
                return $this->reserveCampaignAllocation($workspace, $data, $type);
            }

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

            $reservation = $this->createReservation(
                $workspace,
                $workspaceBeneficiary,
                $data,
                $allocation,
            );

            return ConsultationEligibilityResult::available($reservation);
        });
    }

    private function reserveCampaignAllocation(
        Workspace $workspace,
        ConsultationEligibilityData $data,
        ConsultationType $type,
    ): ConsultationEligibilityResult {
        $beneficiaries = WorkspaceBeneficiary::query()
            ->whereBelongsTo($workspace)
            ->where('relatable_type', (new Campaign)->getMorphClass())
            ->where('beneficiary_id', $data->patientId)
            ->where('status', WorkspaceBeneficiaryStatus::Active)
            ->orderBy('relatable_id')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $existing = Consultation::query()
            ->whereBelongsTo($workspace)
            ->where('beneficiary_id', $data->patientId)
            ->where('doctor_id', $data->doctorId)
            ->where('consultation_type', $type)
            ->where('status', ConsultationReservationStatus::Reserved)
            ->latest('id')
            ->first();

        if ($existing instanceof Consultation) {
            return ConsultationEligibilityResult::available($existing);
        }

        if ($beneficiaries->isEmpty()) {
            return ConsultationEligibilityResult::unavailable('patient_not_eligible', $type);
        }

        foreach ($beneficiaries as $workspaceBeneficiary) {
            $campaign = Campaign::query()
                ->whereBelongsTo($workspace)
                ->whereKey($workspaceBeneficiary->relatable_id)
                ->lockForUpdate()
                ->first();

            if (! $campaign instanceof Campaign) {
                continue;
            }

            $limit = (int) CampaignConsultationQuota::query()
                ->whereBelongsTo($workspace)
                ->whereBelongsTo($campaign)
                ->where('consultation_type', $type)
                ->sum('quantity');
            $usage = Consultation::query()
                ->whereBelongsTo($workspace)
                ->whereIn('workspace_beneficiary_id', $campaign->beneficiaries()->select('id'))
                ->where('consultation_type', $type)
                ->whereIn('status', [
                    ConsultationReservationStatus::Reserved,
                    ConsultationReservationStatus::Confirmed,
                ])
                ->count();

            if ($limit <= $usage) {
                continue;
            }

            $allocation = new ConsultationAllocation(
                subscriptionId: null,
                planId: null,
                planName: $campaign->name,
                type: $type,
                featureSlug: $this->campaignFeatureSlug($type),
                scope: ConsultationAllocationScope::Shared,
                workspaceBeneficiaryId: (int) $workspaceBeneficiary->getKey(),
                limit: $limit,
                periodStart: $campaign->start_date?->toImmutable()->startOfDay()
                    ?? $campaign->created_at?->toImmutable()->startOfDay()
                    ?? CarbonImmutable::now()->startOfDay(),
                periodEnd: $campaign->end_date?->toImmutable()->endOfDay()
                    ?? CarbonImmutable::create(2037, 12, 31, 23, 59, 59),
            );
            $reservation = $this->createReservation(
                $workspace,
                $workspaceBeneficiary,
                $data,
                $allocation,
            );

            return ConsultationEligibilityResult::available($reservation);
        }

        return ConsultationEligibilityResult::unavailable('allocation_exhausted', $type);
    }

    private function createReservation(
        Workspace $workspace,
        WorkspaceBeneficiary $workspaceBeneficiary,
        ConsultationEligibilityData $data,
        ConsultationAllocation $allocation,
    ): Consultation {
        return Consultation::query()->create([
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
    }

    private function campaignFeatureSlug(ConsultationType $type): string
    {
        return match ($type) {
            ConsultationType::GeneralPractitioner => 'campaign-gp-consultations',
            ConsultationType::Specialist => 'campaign-specialist-consultations',
        };
    }
}
