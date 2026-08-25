<?php

namespace App\Actions\Campaigns;

use App\DTOs\Campaigns\PurchaseConsultationQuotaData;
use App\Enums\Consultations\ConsultationType;
use App\Enums\Transactions\TransactionFlow;
use App\Enums\Transactions\TransactionStatus;
use App\Enums\Transactions\TransactionTypes;
use App\Exceptions\Payments\CheckoutUnavailable;
use App\Models\Campaign;
use App\Models\CampaignConsultationQuota;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Support\Payments\PaymentReferenceGenerator;
use App\ValueObjects\Money;
use Illuminate\Support\Facades\DB;

final readonly class PurchaseConsultationQuotaAction
{
    public function __construct(
        private PaymentReferenceGenerator $references,
    ) {}

    public function execute(PurchaseConsultationQuotaData $data): CampaignConsultationQuota
    {
        return DB::transaction(function () use ($data): CampaignConsultationQuota {
            $campaign = Campaign::query()
                ->whereBelongsTo($data->workspace)
                ->whereKey($data->campaign->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $unitFee = Money::fromMajor(
                $this->resolveFee($campaign, $data->consultationType),
                'NGN',
            );
            $totalCost = $unitFee->multiply($data->quantity);
            $wallet = $this->lockedWallet($data);
            $balance = Money::fromMajor($wallet->balance, $wallet->currency);

            if ($balance->currency !== $totalCost->currency) {
                throw new CheckoutUnavailable('The workspace wallet currency does not match this purchase.');
            }

            if ($balance->amountInMinorUnits < $totalCost->amountInMinorUnits) {
                throw new CheckoutUnavailable('Your wallet balance is insufficient for this purchase.');
            }

            $quota = CampaignConsultationQuota::query()->create([
                'campaign_id' => $campaign->getKey(),
                'workspace_id' => $data->workspace->getKey(),
                'consultation_type' => $data->consultationType,
                'quantity' => $data->quantity,
                'unit_fee' => $unitFee->toMajorAmount(),
                'total_cost' => $totalCost->toMajorAmount(),
                'reference' => $this->references->generateQuota(),
            ]);

            $wallet->update([
                'balance' => (new Money(
                    $balance->amountInMinorUnits - $totalCost->amountInMinorUnits,
                    $balance->currency,
                ))->toMajorAmount(),
            ]);

            Transaction::query()->create([
                'payment_id' => null,
                'owner_type' => $data->workspace->getMorphClass(),
                'owner_id' => $data->workspace->getKey(),
                'transactable_type' => $quota->getMorphClass(),
                'transactable_id' => $quota->getKey(),
                'amount' => $totalCost->toMajorAmount(),
                'currency' => $totalCost->currency,
                'reference' => $quota->reference,
                'type' => TransactionTypes::CONSULTATION_QUOTA,
                'status' => TransactionStatus::COMPLETED,
                'flow' => TransactionFlow::DEBIT,
                'meta' => [
                    'description' => sprintf(
                        '%s consultation quota purchase for %s',
                        $data->consultationType->label(),
                        $campaign->name,
                    ),
                    'quantity' => $data->quantity,
                    'campaign_id' => $campaign->getKey(),
                    'consultation_type' => $data->consultationType->value,
                    'purchased_by_user_id' => $data->user->getKey(),
                ],
            ]);

            return $quota;
        }, 3);
    }

    private function resolveFee(Campaign $campaign, ConsultationType $type): string
    {
        $fee = match ($type) {
            ConsultationType::GeneralPractitioner => $campaign->gp_fee,
            ConsultationType::Specialist => $campaign->specialist_fee,
        };

        if ($fee === null || (float) $fee <= 0) {
            throw new CheckoutUnavailable(
                'The '.$type->label().' fee has not been set for this campaign.',
            );
        }

        return $fee;
    }

    private function lockedWallet(PurchaseConsultationQuotaData $data): Wallet
    {
        $wallet = $data->workspace->wallet()->firstOrCreate([], [
            'balance' => '0.00',
            'currency' => 'NGN',
        ]);

        return Wallet::query()->whereKey($wallet->getKey())->lockForUpdate()->firstOrFail();
    }
}
