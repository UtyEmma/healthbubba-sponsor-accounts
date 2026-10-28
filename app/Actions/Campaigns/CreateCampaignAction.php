<?php

namespace App\Actions\Campaigns;

use App\DTOs\Campaigns\CreateCampaignData;
use App\Enums\CampaignBoothStatus;
use App\Enums\CampaignRecurringCostCategory;
use App\Enums\CampaignStatus;
use App\Enums\Consultations\ConsultationType;
use App\Enums\Transactions\TransactionFlow;
use App\Enums\Transactions\TransactionStatus;
use App\Enums\Transactions\TransactionTypes;
use App\Models\Campaign;
use App\Models\CampaignBooth;
use App\Models\CampaignConsultationQuota;
use App\Models\CampaignEnrollmentCode;
use App\Models\CampaignRecurringCost;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\Workspace;
use App\Services\Campaigns\CampaignPricingService;
use App\Support\Payments\PaymentReferenceGenerator;
use App\ValueObjects\Money;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class CreateCampaignAction
{
    public function __construct(
        private PaymentReferenceGenerator $references,
        private CampaignPricingService $pricing,
    ) {}

    public function execute(CreateCampaignData $data): Campaign
    {
        $scheduledFee = Money::fromMajor((string) config('campaigns.default_scheduled_consultation_fee'), 'NGN');
        $instantFee = Money::fromMajor((string) config('campaigns.default_instant_consultation_fee'), 'NGN');
        $boothSetupFee = Money::fromMajor((string) config('campaigns.booth_setup_fee'), 'NGN');
        $boothMonthlyFee = Money::fromMajor((string) config('campaigns.booth_monthly_fee'), 'NGN');
        $scheduledAllocation = $scheduledFee->multiply($data->healthcare->scheduledUnits);
        $instantAllocation = $instantFee->multiply($data->healthcare->instantUnits);
        $medication = Money::fromMajor($data->healthcare->medicationBudget, 'NGN');
        $laboratory = Money::fromMajor($data->healthcare->laboratoryBudget, 'NGN');
        $healthcareAllocation = $scheduledAllocation->add($instantAllocation)->add($medication)->add($laboratory);
        $boothSetupTotal = $data->booth->required
            ? $boothSetupFee->multiply($data->booth->count ?? 0)
            : new Money(0, 'NGN');

        return DB::transaction(function () use (
            $data,
            $scheduledFee,
            $instantFee,
            $boothSetupFee,
            $boothMonthlyFee,
            $healthcareAllocation,
            $boothSetupTotal,
        ): Campaign {
            $workspace = Workspace::query()->whereKey($data->workspace->getKey())->lockForUpdate()->firstOrFail();
            $wallet = $workspace->wallet()->firstOrCreate([], ['balance' => '0.00', 'currency' => 'NGN']);
            $wallet = Wallet::query()->whereKey($wallet->getKey())->lockForUpdate()->firstOrFail();
            $balance = Money::fromMajor($wallet->balance, $wallet->currency);

            if ($balance->currency !== $healthcareAllocation->currency) {
                throw ValidationException::withMessages([
                    'launch' => 'The workspace wallet currency does not match this campaign.',
                ]);
            }

            $pricing = $this->pricing->quote(
                beneficiaries: $data->enrollment->estimatedBeneficiaries,
                currency: $healthcareAllocation->currency,
                lockForUpdate: true,
            );
            $beneficiarySetupFee = Money::fromMajor($pricing->beneficiarySetupFee, $pricing->currency);
            $monthlyServiceFee = $pricing->monthlyServiceFee === null
                ? null
                : Money::fromMajor($pricing->monthlyServiceFee, $pricing->currency);
            $launchTotal = $healthcareAllocation
                ->add($boothSetupTotal)
                ->add($beneficiarySetupFee);

            if ($balance->currency !== $launchTotal->currency) {
                throw ValidationException::withMessages([
                    'launch' => 'The workspace wallet currency does not match this campaign.',
                ]);
            }

            if ($balance->amountInMinorUnits < $launchTotal->amountInMinorUnits) {
                throw ValidationException::withMessages([
                    'launch' => 'Your available wallet balance is insufficient to launch this campaign.',
                ]);
            }

            $allocationReference = $this->references->generateCampaignAllocation();
            $beneficiarySetupReference = $this->references->generateCampaignBeneficiarySetup();
            $boothSetupReference = $data->booth->required ? $this->references->generateBoothCharge() : null;
            $startDate = CarbonImmutable::parse($data->details->startDate);
            $displayEnrollmentCode = $this->displayEnrollmentCode($data, $workspace->getKey());
            $campaign = $workspace->campaigns()->create([
                'name' => $data->details->name,
                'description' => $data->details->description,
                'location' => $data->details->locations,
                'enrollment_method' => $data->enrollment->method,
                'estimated_beneficiaries' => $data->enrollment->estimatedBeneficiaries,
                'campaign_pricing_tier_id' => $pricing->tierId,
                'beneficiary_unit_fee' => $pricing->beneficiaryUnitFee,
                'beneficiary_setup_fee' => $pricing->beneficiarySetupFee,
                'monthly_service_fee' => $pricing->monthlyServiceFee,
                'beneficiary_limit' => null,
                'start_date' => $data->details->startDate,
                'end_date' => $data->details->endDate,
                'status' => $startDate->isAfter(CarbonImmutable::today())
                    ? CampaignStatus::PENDING
                    : CampaignStatus::IN_PROGRESS,
                'scheduled_consultation_fee' => $scheduledFee->toMajorAmount(),
                'instant_consultation_fee' => $instantFee->toMajorAmount(),
                'currency' => $launchTotal->currency,
                'medication_budget' => $data->healthcare->medicationBudget,
                'laboratory_budget' => $data->healthcare->laboratoryBudget,
                'allocation_reference' => $allocationReference,
                'display_enrollment_code' => $displayEnrollmentCode,
                'launched_at' => now(),
                'booth_required' => $data->booth->required,
                'booth_count' => $data->booth->count,
                'booth_preferred_deployment_date' => $data->booth->preferredDeploymentDate,
                'booth_site' => $data->booth->site,
                'booth_contact_name' => $data->booth->contactName,
                'booth_contact_phone' => $data->booth->contactPhone,
                'booth_setup_unit_fee' => $data->booth->required ? $boothSetupFee->toMajorAmount() : null,
                'booth_monthly_unit_fee' => $data->booth->required ? $boothMonthlyFee->toMajorAmount() : null,
                'coverage_type_override' => $data->coverageTypeOverride,
                'scheduled_consultation_limit_per_beneficiary_override' => $data->scheduledLimitOverride,
                'instant_consultation_limit_per_beneficiary_override' => $data->instantLimitOverride,
                'daily_consultation_limit_override' => $data->dailyLimitOverride,
                'coverage_expiry_override' => $data->coverageExpiryOverride,
                'payment_preference_override' => $data->paymentPreferenceOverride,
            ]);

            CampaignEnrollmentCode::query()->create([
                'public_id' => (string) Str::ulid(),
                'campaign_id' => $campaign->getKey(),
                'created_by_user_id' => $data->user->getKey(),
                'code' => $displayEnrollmentCode,
                'enrollment_limit' => max(1, $data->enrollment->estimatedBeneficiaries),
                'expires_at' => $data->details->endDate,
            ]);

            $this->createQuota($campaign, $workspace, ConsultationType::Scheduled, $data->healthcare->scheduledUnits, $scheduledFee);
            $this->createQuota($campaign, $workspace, ConsultationType::Instant, $data->healthcare->instantUnits, $instantFee);

            if ($monthlyServiceFee !== null) {
                CampaignRecurringCost::query()->create([
                    'campaign_id' => $campaign->getKey(),
                    'workspace_id' => $workspace->getKey(),
                    'name' => 'Campaign management & service',
                    'category' => CampaignRecurringCostCategory::CampaignService,
                    'monthly_amount' => $monthlyServiceFee->toMajorAmount(),
                    'currency' => $monthlyServiceFee->currency,
                    'starts_on' => $data->details->startDate,
                    'next_charge_on' => $data->details->startDate,
                    'ends_on' => $data->details->endDate,
                    'is_active' => true,
                ]);
            }

            if ($data->booth->required && $data->booth->count !== null) {
                $this->createBooths(
                    campaign: $campaign,
                    workspace: $workspace,
                    data: $data,
                    setupFee: $boothSetupFee,
                    monthlyFee: $boothMonthlyFee,
                    setupReference: (string) $boothSetupReference,
                );
            }

            $wallet->update([
                'balance' => (new Money(
                    $balance->amountInMinorUnits - $launchTotal->amountInMinorUnits,
                    $balance->currency,
                ))->toMajorAmount(),
            ]);

            if ($healthcareAllocation->amountInMinorUnits > 0) {
                $this->recordDebit(
                    workspace: $workspace,
                    campaign: $campaign,
                    amount: $healthcareAllocation,
                    reference: $allocationReference,
                    type: TransactionTypes::CAMPAIGN_ALLOCATION,
                    description: "Healthcare allocation reserved for {$campaign->name}",
                    userId: (int) $data->user->getKey(),
                );
            }

            if ($boothSetupTotal->amountInMinorUnits > 0) {
                $this->recordDebit(
                    workspace: $workspace,
                    campaign: $campaign,
                    amount: $boothSetupTotal,
                    reference: (string) $boothSetupReference,
                    type: TransactionTypes::CAMPAIGN_BOOTH_SETUP,
                    description: "Booth setup for {$campaign->name}",
                    userId: (int) $data->user->getKey(),
                );
            }

            if ($beneficiarySetupFee->amountInMinorUnits > 0) {
                $this->recordDebit(
                    workspace: $workspace,
                    campaign: $campaign,
                    amount: $beneficiarySetupFee,
                    reference: $beneficiarySetupReference,
                    type: TransactionTypes::CAMPAIGN_BENEFICIARY_SETUP,
                    description: "Beneficiary setup for {$campaign->name}",
                    userId: (int) $data->user->getKey(),
                );
            }

            if ($workspace->onboarded_at === null) {
                $workspace->update(['onboarded_at' => now()]);
            }

            return $campaign->load(['consultationQuotas', 'booths']);
        }, 3);
    }

    private function displayEnrollmentCode(CreateCampaignData $data, int $workspaceId): string
    {
        $prefix = Str::of($data->details->locations)
            ->before(',')
            ->slug('-')
            ->upper()
            ->limit(18, '')
            ->toString();
        $year = CarbonImmutable::parse($data->details->endDate)->format('Y');

        return "{$prefix}-{$year}-{$workspaceId}-".Str::upper(Str::random(4));
    }

    private function createBooths(
        Campaign $campaign,
        Workspace $workspace,
        CreateCampaignData $data,
        Money $setupFee,
        Money $monthlyFee,
        string $setupReference,
    ): void {
        foreach (range(1, (int) $data->booth->count) as $position) {
            $name = (string) $data->booth->site;

            if ((int) $data->booth->count > 1) {
                $name .= " {$position}";
            }

            $booth = CampaignBooth::query()->create([
                'public_id' => (string) Str::ulid(),
                'campaign_id' => $campaign->getKey(),
                'workspace_id' => $workspace->getKey(),
                'name' => $name,
                'site' => $data->booth->site,
                'community' => Str::of($data->details->locations)->before(',')->trim()->toString(),
                'expected_beneficiaries' => $data->enrollment->estimatedBeneficiaries,
                'contact_name' => $data->booth->contactName,
                'contact_phone' => $data->booth->contactPhone,
                'preferred_deployment_date' => $data->booth->preferredDeploymentDate,
                'setup_fee' => $setupFee->toMajorAmount(),
                'monthly_fee' => $monthlyFee->toMajorAmount(),
                'currency' => $campaign->currency,
                'status' => CampaignBoothStatus::Requested,
                'setup_reference' => $setupReference,
                'setup_paid_at' => now(),
            ]);

            CampaignRecurringCost::query()->create([
                'campaign_id' => $campaign->getKey(),
                'workspace_id' => $workspace->getKey(),
                'campaign_booth_id' => $booth->getKey(),
                'name' => 'Booth management & service',
                'category' => CampaignRecurringCostCategory::BoothService,
                'monthly_amount' => $monthlyFee->toMajorAmount(),
                'currency' => $campaign->currency,
                'starts_on' => $data->booth->preferredDeploymentDate,
                'is_active' => false,
            ]);
        }
    }

    private function createQuota(
        Campaign $campaign,
        Workspace $workspace,
        ConsultationType $type,
        int $quantity,
        Money $unitFee,
    ): void {
        if ($quantity === 0) {
            return;
        }

        CampaignConsultationQuota::query()->create([
            'campaign_id' => $campaign->getKey(),
            'workspace_id' => $workspace->getKey(),
            'consultation_type' => $type,
            'quantity' => $quantity,
            'unit_fee' => $unitFee->toMajorAmount(),
            'total_cost' => $unitFee->multiply($quantity)->toMajorAmount(),
            'reference' => $this->references->generateQuota(),
        ]);
    }

    private function recordDebit(
        Workspace $workspace,
        Campaign $campaign,
        Money $amount,
        string $reference,
        TransactionTypes $type,
        string $description,
        int $userId,
    ): void {
        Transaction::query()->create([
            'recorded_by_user_id' => $userId,
            'owner_type' => $workspace->getMorphClass(),
            'owner_id' => $workspace->getKey(),
            'transactable_type' => $campaign->getMorphClass(),
            'transactable_id' => $campaign->getKey(),
            'amount' => $amount->toMajorAmount(),
            'currency' => $amount->currency,
            'reference' => $reference,
            'type' => $type,
            'status' => TransactionStatus::COMPLETED,
            'flow' => TransactionFlow::DEBIT,
            'meta' => [
                'description' => $description,
                'campaign_id' => $campaign->getKey(),
                'created_by_user_id' => $userId,
            ],
        ]);
    }
}
