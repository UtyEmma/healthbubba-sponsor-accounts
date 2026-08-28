<?php

namespace App\Actions\Campaigns;

use App\Enums\CampaignBoothChargeStatus;
use App\Enums\Transactions\TransactionFlow;
use App\Enums\Transactions\TransactionStatus;
use App\Enums\Transactions\TransactionTypes;
use App\Models\Campaign;
use App\Models\CampaignBoothCharge;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Support\Payments\PaymentReferenceGenerator;
use App\ValueObjects\Money;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final readonly class BillCampaignBoothAction
{
    public function __construct(private PaymentReferenceGenerator $references) {}

    public function execute(Campaign $campaign): bool
    {
        return DB::transaction(function () use ($campaign): bool {
            $campaign = Campaign::query()->whereKey($campaign->getKey())->lockForUpdate()->firstOrFail();

            if (! $this->isBillable($campaign)) {
                return false;
            }

            $latest = $campaign->boothCharges()->latest('service_period')->first();
            $servicePeriod = $latest instanceof CampaignBoothCharge
                && $latest->status === CampaignBoothChargeStatus::Pending
                    ? $latest->service_period->toImmutable()
                    : $campaign->booth_activated_at
                        ->toImmutable()
                        ->startOfDay()
                        ->addMonthsNoOverflow(
                            $campaign->boothCharges()
                                ->where('status', CampaignBoothChargeStatus::Paid)
                                ->count(),
                        );

            if ($servicePeriod->isAfter(CarbonImmutable::today())) {
                return false;
            }

            $unitFee = Money::fromMajor($campaign->booth_monthly_unit_fee ?? '0.00', $campaign->currency);
            $total = $unitFee->multiply($campaign->booth_count ?? 0);
            $charge = $campaign->boothCharges()->firstOrCreate(
                ['service_period' => $servicePeriod->toDateString()],
                [
                    'workspace_id' => $campaign->workspace_id,
                    'quantity' => $campaign->booth_count,
                    'unit_fee' => $unitFee->toMajorAmount(),
                    'total_cost' => $total->toMajorAmount(),
                    'currency' => $total->currency,
                    'status' => CampaignBoothChargeStatus::Pending,
                ],
            );
            $wallet = $campaign->workspace->wallet()->firstOrCreate([], [
                'balance' => '0.00',
                'currency' => $campaign->currency,
            ]);
            $wallet = Wallet::query()->whereKey($wallet->getKey())->lockForUpdate()->firstOrFail();
            $balance = Money::fromMajor($wallet->balance, $wallet->currency);

            if ($balance->currency !== $total->currency
                || $balance->amountInMinorUnits < $total->amountInMinorUnits) {
                $charge->update(['attempted_at' => now()]);

                return false;
            }

            $reference = $charge->reference ?? $this->references->generateBoothCharge();
            $wallet->update([
                'balance' => (new Money(
                    $balance->amountInMinorUnits - $total->amountInMinorUnits,
                    $balance->currency,
                ))->toMajorAmount(),
            ]);
            $charge->update([
                'status' => CampaignBoothChargeStatus::Paid,
                'reference' => $reference,
                'attempted_at' => now(),
                'paid_at' => now(),
            ]);
            $campaign->update(['booth_last_billed_at' => now()]);

            Transaction::query()->create([
                'owner_type' => $campaign->workspace->getMorphClass(),
                'owner_id' => $campaign->workspace->getKey(),
                'transactable_type' => $charge->getMorphClass(),
                'transactable_id' => $charge->getKey(),
                'amount' => $total->toMajorAmount(),
                'currency' => $total->currency,
                'reference' => $reference,
                'type' => TransactionTypes::CAMPAIGN_BOOTH_SERVICE,
                'status' => TransactionStatus::COMPLETED,
                'flow' => TransactionFlow::DEBIT,
                'meta' => [
                    'description' => "Monthly booth service for {$campaign->name}",
                    'campaign_id' => $campaign->getKey(),
                    'service_period' => $servicePeriod->toDateString(),
                ],
            ]);

            return true;
        }, 3);
    }

    private function isBillable(Campaign $campaign): bool
    {
        return $campaign->booth_required
            && ($campaign->booth_count ?? 0) > 0
            && $campaign->booth_activated_at !== null
            && $campaign->booth_deactivated_at === null
            && $campaign->ended_at === null;
    }
}
