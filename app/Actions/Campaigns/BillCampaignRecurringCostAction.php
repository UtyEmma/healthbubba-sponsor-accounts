<?php

namespace App\Actions\Campaigns;

use App\Enums\CampaignBoothChargeStatus;
use App\Enums\CampaignBoothStatus;
use App\Enums\CampaignRecurringCostCategory;
use App\Enums\Transactions\TransactionFlow;
use App\Enums\Transactions\TransactionStatus;
use App\Enums\Transactions\TransactionTypes;
use App\Models\CampaignBooth;
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
            $booth = $cost->campaign_booth_id === null
                ? null
                : CampaignBooth::query()->whereKey($cost->campaign_booth_id)->lockForUpdate()->firstOrFail();

            if (! $cost->is_active || $campaign->ended_at !== null || $cost->deactivated_at !== null) {
                return false;
            }

            $pending = $cost->charges()
                ->where('status', CampaignBoothChargeStatus::Pending)
                ->oldest('service_period')
                ->lockForUpdate()
                ->first();
            $isSuspendedBooth = $booth?->status === CampaignBoothStatus::Suspended;

            if ($isSuspendedBooth && ! $pending instanceof CampaignRecurringCostCharge) {
                return false;
            }

            $servicePeriod = $pending?->service_period?->toImmutable()
                ?? $cost->next_charge_on?->toImmutable()
                ?? $cost->starts_on->toImmutable();

            if (! $pending instanceof CampaignRecurringCostCharge) {
                $paidPeriods = $cost->charges()
                    ->where('status', CampaignBoothChargeStatus::Paid)
                    ->pluck('service_period')
                    ->map(static fn (mixed $period): string => CarbonImmutable::parse((string) $period)->toDateString());

                while ($paidPeriods->contains($servicePeriod->toDateString())) {
                    $servicePeriod = $servicePeriod->addMonthNoOverflow();
                }

                if ($cost->next_charge_on?->toDateString() !== $servicePeriod->toDateString()) {
                    $cost->update(['next_charge_on' => $servicePeriod->toDateString()]);
                }
            }

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

                if ($booth instanceof CampaignBooth && $cost->category === CampaignRecurringCostCategory::BoothService) {
                    $graceEndsOn = $servicePeriod->addDays((int) config('campaigns.booth_billing_grace_days', 7));
                    $suspended = CarbonImmutable::today()->greaterThanOrEqualTo($graceEndsOn);
                    $booth->update([
                        'status' => $suspended ? CampaignBoothStatus::Suspended : CampaignBoothStatus::GracePeriod,
                        'billing_grace_ends_on' => $graceEndsOn->toDateString(),
                        'billing_suspended_at' => $suspended ? ($booth->billing_suspended_at ?? now()) : null,
                    ]);
                    $cost->update(['next_charge_on' => $servicePeriod->toDateString()]);
                }

                return false;
            }

            $reference = $charge->reference ?? $this->references->generateRecurringCostCharge();
            $wallet->update(['balance' => (new Money($balance->amountInMinorUnits - $amount->amountInMinorUnits, $balance->currency))->toMajorAmount()]);
            $charge->update(['status' => CampaignBoothChargeStatus::Paid, 'reference' => $reference, 'attempted_at' => now(), 'paid_at' => now()]);
            $recovery = $booth?->status === CampaignBoothStatus::Suspended
                || ($booth?->billing_grace_ends_on !== null
                    && CarbonImmutable::today()->greaterThanOrEqualTo($booth->billing_grace_ends_on));
            $nextChargeOn = $recovery
                ? CarbonImmutable::today()->addMonthNoOverflow()
                : $servicePeriod->addMonthNoOverflow();
            $cost->update(['next_charge_on' => $nextChargeOn->toDateString()]);

            if ($booth instanceof CampaignBooth) {
                $booth->update([
                    'status' => CampaignBoothStatus::Active,
                    'last_billed_at' => now(),
                    'paid_through' => $nextChargeOn->subDay()->toDateString(),
                    'billing_grace_ends_on' => null,
                    'billing_suspended_at' => null,
                ]);
            }

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
