<?php

namespace App\Actions\Campaigns;

use App\Enums\CampaignBoothChargeStatus;
use App\Enums\CampaignRecurringCostCategory;
use App\Enums\Transactions\TransactionFlow;
use App\Enums\Transactions\TransactionStatus;
use App\Enums\Transactions\TransactionTypes;
use App\Models\CampaignRecurringCost;
use App\Models\CampaignRecurringCostCharge;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Support\Payments\PaymentReferenceGenerator;
use App\ValueObjects\Money;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final readonly class BillCampaignRecurringCostAction
{
    public function __construct(private PaymentReferenceGenerator $references) {}

    public function execute(CampaignRecurringCost $cost): bool
    {
        return DB::transaction(function () use ($cost): bool {
            $cost = CampaignRecurringCost::query()->whereKey($cost->getKey())->lockForUpdate()->firstOrFail();
            $campaign = $cost->campaign()->with('workspace')->lockForUpdate()->firstOrFail();

            if (! $cost->is_active || $campaign->ended_at !== null || $cost->deactivated_at !== null) {
                return false;
            }

            $paidCount = $cost->charges()->where('status', CampaignBoothChargeStatus::Paid)->count();
            $pending = $cost->charges()->where('status', CampaignBoothChargeStatus::Pending)->oldest('service_period')->first();
            $servicePeriod = $pending?->service_period?->toImmutable()
                ?? $cost->starts_on->toImmutable()->addMonthsNoOverflow($paidCount);

            if ($servicePeriod->isAfter(CarbonImmutable::today())
                || ($cost->ends_on !== null && $servicePeriod->isAfter($cost->ends_on))) {
                return false;
            }

            $amount = $pending instanceof CampaignRecurringCostCharge
                ? Money::fromMajor($pending->amount, $pending->currency)
                : Money::fromMajor($cost->monthly_amount, $cost->currency);
            $charge = $pending ?? CampaignRecurringCostCharge::query()->firstOrCreate(
                ['campaign_recurring_cost_id' => $cost->getKey(), 'service_period' => $servicePeriod->toDateString()],
                [
                    'campaign_id' => $campaign->getKey(),
                    'workspace_id' => $cost->workspace_id,
                    'amount' => $amount->toMajorAmount(),
                    'currency' => $amount->currency,
                    'status' => CampaignBoothChargeStatus::Pending,
                ],
            );
            $wallet = $campaign->workspace->wallet()->firstOrCreate([], ['balance' => '0.00', 'currency' => $campaign->currency]);
            $wallet = Wallet::query()->whereKey($wallet->getKey())->lockForUpdate()->firstOrFail();
            $balance = Money::fromMajor($wallet->balance, $wallet->currency);

            if ($balance->currency !== $amount->currency || $balance->amountInMinorUnits < $amount->amountInMinorUnits) {
                $charge->update(['attempted_at' => now()]);

                return false;
            }

            $reference = $charge->reference ?? $this->references->generateRecurringCostCharge();
            $wallet->update(['balance' => (new Money($balance->amountInMinorUnits - $amount->amountInMinorUnits, $balance->currency))->toMajorAmount()]);
            $charge->update(['status' => CampaignBoothChargeStatus::Paid, 'reference' => $reference, 'attempted_at' => now(), 'paid_at' => now()]);
            $cost->booth()->update([
                'last_billed_at' => now(),
                'paid_through' => $servicePeriod->addMonthNoOverflow()->subDay()->toDateString(),
            ]);

            Transaction::query()->create([
                'owner_type' => $campaign->workspace->getMorphClass(),
                'owner_id' => $campaign->workspace_id,
                'transactable_type' => $charge->getMorphClass(),
                'transactable_id' => $charge->getKey(),
                'amount' => $amount->toMajorAmount(),
                'currency' => $amount->currency,
                'reference' => $reference,
                'type' => $cost->category === CampaignRecurringCostCategory::BoothService
                    ? TransactionTypes::CAMPAIGN_BOOTH_SERVICE
                    : TransactionTypes::CAMPAIGN_OPERATING_COST,
                'status' => TransactionStatus::COMPLETED,
                'flow' => TransactionFlow::DEBIT,
                'meta' => [
                    'description' => "{$cost->name} for {$campaign->name}",
                    'campaign_id' => $campaign->getKey(),
                    'service_period' => $servicePeriod->toDateString(),
                ],
            ]);

            return true;
        }, 3);
    }
}
