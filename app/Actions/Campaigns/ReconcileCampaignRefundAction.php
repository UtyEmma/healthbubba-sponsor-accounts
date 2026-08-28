<?php

namespace App\Actions\Campaigns;

use App\Enums\CampaignStatus;
use App\Enums\Consultations\ConsultationReservationStatus;
use App\Enums\Consultations\ConsultationType;
use App\Enums\Transactions\TransactionFlow;
use App\Enums\Transactions\TransactionStatus;
use App\Enums\Transactions\TransactionTypes;
use App\Models\Campaign;
use App\Models\Consultations\Consultation;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Support\Payments\PaymentReferenceGenerator;
use App\ValueObjects\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final readonly class ReconcileCampaignRefundAction
{
    public function __construct(private PaymentReferenceGenerator $references) {}

    public function execute(Campaign $campaign): string
    {
        return DB::transaction(function () use ($campaign): string {
            $campaign = Campaign::query()->whereKey($campaign->getKey())->lockForUpdate()->firstOrFail();

            if ($campaign->lifecycleStatus() !== CampaignStatus::COMPLETED) {
                return '0.00';
            }

            $allocated = Money::fromMajor((string) $campaign->consultationQuotas()->sum('total_cost'), $campaign->currency)
                ->add(Money::fromMajor($campaign->medication_budget, $campaign->currency))
                ->add(Money::fromMajor($campaign->laboratory_budget, $campaign->currency));
            $usageCounts = Consultation::query()
                ->whereIn('workspace_beneficiary_id', $campaign->beneficiaries()->select('id'))
                ->whereIn('status', [
                    ConsultationReservationStatus::Reserved,
                    ConsultationReservationStatus::Confirmed,
                ])
                ->selectRaw('consultation_type, status, COUNT(*) AS aggregate')
                ->groupBy('consultation_type', 'status')
                ->get();
            $spent = $this->consultationValue($campaign, $usageCounts, ConsultationReservationStatus::Confirmed)
                ->add(Money::fromMajor((string) $campaign->budgetUsages()->sum('amount'), $campaign->currency));
            $encumbered = $this->consultationValue($campaign, $usageCounts, ConsultationReservationStatus::Reserved);
            $refundableMinor = max(
                0,
                $allocated->amountInMinorUnits - $spent->amountInMinorUnits - $encumbered->amountInMinorUnits,
            );
            $alreadyReturned = Money::fromMajor($campaign->returned_amount, $campaign->currency);
            $refundMinor = max(0, $refundableMinor - $alreadyReturned->amountInMinorUnits);

            if ($refundMinor === 0) {
                return '0.00';
            }

            $refund = new Money($refundMinor, $campaign->currency);
            $wallet = $campaign->workspace->wallet()->firstOrCreate([], [
                'balance' => '0.00',
                'currency' => $campaign->currency,
            ]);
            $wallet = Wallet::query()->whereKey($wallet->getKey())->lockForUpdate()->firstOrFail();
            $balance = Money::fromMajor($wallet->balance, $wallet->currency);

            $wallet->update(['balance' => $balance->add($refund)->toMajorAmount()]);
            $campaign->update([
                'returned_amount' => (new Money($refundableMinor, $campaign->currency))->toMajorAmount(),
            ]);

            Transaction::query()->create([
                'owner_type' => $campaign->workspace->getMorphClass(),
                'owner_id' => $campaign->workspace->getKey(),
                'transactable_type' => $campaign->getMorphClass(),
                'transactable_id' => $campaign->getKey(),
                'amount' => $refund->toMajorAmount(),
                'currency' => $refund->currency,
                'reference' => $this->references->generateCampaignRefund(),
                'type' => TransactionTypes::CAMPAIGN_REFUND,
                'status' => TransactionStatus::COMPLETED,
                'flow' => TransactionFlow::CREDIT,
                'meta' => [
                    'description' => "Unused campaign allocation returned for {$campaign->name}",
                    'campaign_id' => $campaign->getKey(),
                ],
            ]);

            return $refund->toMajorAmount();
        }, 3);
    }

    /** @param Collection<int, Consultation> $counts */
    private function consultationValue(
        Campaign $campaign,
        Collection $counts,
        ConsultationReservationStatus $status,
    ): Money {
        $gpCount = (int) $counts
            ->first(fn (Consultation $row): bool => $row->consultation_type === ConsultationType::GeneralPractitioner && $row->status === $status)
            ?->getAttribute('aggregate');
        $specialistCount = (int) $counts
            ->first(fn (Consultation $row): bool => $row->consultation_type === ConsultationType::Specialist && $row->status === $status)
            ?->getAttribute('aggregate');

        return Money::fromMajor($campaign->gp_fee ?? '0.00', $campaign->currency)
            ->multiply($gpCount)
            ->add(
                Money::fromMajor($campaign->specialist_fee ?? '0.00', $campaign->currency)
                    ->multiply($specialistCount),
            );
    }
}
