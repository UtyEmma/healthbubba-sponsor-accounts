<?php

namespace App\Actions\Campaigns;

use App\DTOs\Campaigns\AllocateMoreToCampaignData;
use App\Enums\CampaignStatus;
use App\Enums\Consultations\ConsultationType;
use App\Enums\Transactions\TransactionFlow;
use App\Enums\Transactions\TransactionStatus;
use App\Enums\Transactions\TransactionTypes;
use App\Models\Campaign;
use App\Models\CampaignConsultationQuota;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Support\Payments\PaymentReferenceGenerator;
use App\ValueObjects\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class AllocateMoreToCampaignAction
{
    public function __construct(private PaymentReferenceGenerator $references) {}

    public function execute(AllocateMoreToCampaignData $data): Campaign
    {
        return DB::transaction(function () use ($data): Campaign {
            $campaign = Campaign::query()
                ->whereBelongsTo($data->workspace)
                ->whereKey($data->campaign->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($campaign->lifecycleStatus() === CampaignStatus::COMPLETED) {
                throw ValidationException::withMessages(['campaign' => 'An ended campaign cannot receive another allocation.']);
            }

            $gpFee = Money::fromMajor($campaign->gp_fee ?? (string) config('campaigns.default_gp_fee'), $campaign->currency);
            $specialistFee = Money::fromMajor($campaign->specialist_fee ?? (string) config('campaigns.default_specialist_fee'), $campaign->currency);
            $medication = Money::fromMajor($data->medicationBudget, $campaign->currency);
            $laboratory = Money::fromMajor($data->laboratoryBudget, $campaign->currency);
            $total = $gpFee->multiply($data->gpUnits)
                ->add($specialistFee->multiply($data->specialistUnits))
                ->add($medication)
                ->add($laboratory);

            if ($total->amountInMinorUnits === 0) {
                throw ValidationException::withMessages(['allocation' => 'Add at least one healthcare benefit allocation.']);
            }

            $wallet = $data->workspace->wallet()->firstOrCreate([], ['balance' => '0.00', 'currency' => $campaign->currency]);
            $wallet = Wallet::query()->whereKey($wallet->getKey())->lockForUpdate()->firstOrFail();
            $balance = Money::fromMajor($wallet->balance, $wallet->currency);

            if ($balance->currency !== $total->currency || $balance->amountInMinorUnits < $total->amountInMinorUnits) {
                throw ValidationException::withMessages(['allocation' => 'Your available wallet balance is insufficient for this allocation.']);
            }

            $reference = $this->references->generateCampaignAllocation();
            $this->createQuota($campaign, $data, ConsultationType::GeneralPractitioner, $data->gpUnits, $gpFee);
            $this->createQuota($campaign, $data, ConsultationType::Specialist, $data->specialistUnits, $specialistFee);
            $campaign->update([
                'medication_budget' => Money::fromMajor($campaign->medication_budget, $campaign->currency)->add($medication)->toMajorAmount(),
                'laboratory_budget' => Money::fromMajor($campaign->laboratory_budget, $campaign->currency)->add($laboratory)->toMajorAmount(),
            ]);
            $wallet->update(['balance' => (new Money($balance->amountInMinorUnits - $total->amountInMinorUnits, $balance->currency))->toMajorAmount()]);

            Transaction::query()->create([
                'owner_type' => $data->workspace->getMorphClass(),
                'owner_id' => $data->workspace->getKey(),
                'transactable_type' => $campaign->getMorphClass(),
                'transactable_id' => $campaign->getKey(),
                'amount' => $total->toMajorAmount(),
                'currency' => $total->currency,
                'reference' => $reference,
                'type' => TransactionTypes::CAMPAIGN_ALLOCATION,
                'status' => TransactionStatus::COMPLETED,
                'flow' => TransactionFlow::DEBIT,
                'meta' => [
                    'description' => "Additional healthcare allocation for {$campaign->name}",
                    'campaign_id' => $campaign->getKey(),
                    'created_by_user_id' => $data->user->getKey(),
                    'gp_units' => $data->gpUnits,
                    'specialist_units' => $data->specialistUnits,
                    'medication_budget' => $medication->toMajorAmount(),
                    'laboratory_budget' => $laboratory->toMajorAmount(),
                ],
            ]);

            return $campaign->refresh();
        }, 3);
    }

    private function createQuota(
        Campaign $campaign,
        AllocateMoreToCampaignData $data,
        ConsultationType $type,
        int $quantity,
        Money $unitFee,
    ): void {
        if ($quantity === 0) {
            return;
        }

        CampaignConsultationQuota::query()->create([
            'campaign_id' => $campaign->getKey(),
            'workspace_id' => $data->workspace->getKey(),
            'consultation_type' => $type,
            'quantity' => $quantity,
            'unit_fee' => $unitFee->toMajorAmount(),
            'total_cost' => $unitFee->multiply($quantity)->toMajorAmount(),
            'reference' => $this->references->generateQuota(),
        ]);
    }
}
