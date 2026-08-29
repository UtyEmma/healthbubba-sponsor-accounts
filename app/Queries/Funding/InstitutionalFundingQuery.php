<?php

namespace App\Queries\Funding;

use App\DTOs\Funding\InstitutionalFundingPageData;
use App\Enums\CampaignStatus;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentStatus;
use App\Enums\Transactions\TransactionFlow;
use App\Enums\Transactions\TransactionStatus;
use App\Enums\Transactions\TransactionTypes;
use App\Models\Campaign;
use App\Models\CampaignUsageEntry;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\Workspace;
use App\Queries\InstitutionalCampaigns\CampaignMetricsQuery;
use App\ValueObjects\Money;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

final readonly class InstitutionalFundingQuery
{
    public function __construct(
        private CampaignMetricsQuery $metrics,
    ) {}

    public function get(Workspace $workspace): InstitutionalFundingPageData
    {
        $program = $workspace->fundingProgram()->firstOrFail();
        $campaigns = Campaign::query()
            ->whereBelongsTo($workspace)
            ->orderByRaw("CASE status WHEN 'IN_PROGRESS' THEN 1 WHEN 'PENDING' THEN 2 WHEN 'PAUSED' THEN 3 ELSE 4 END")
            ->orderByDesc('start_date')
            ->get();
        $this->metrics->hydrate($campaigns);

        $wallet = $workspace->wallet()->firstOrFail(['balance', 'currency']);
        $currency = $wallet->currency;
        $available = Money::fromMajor($wallet->balance, $currency);
        $reserved = new Money(0, $currency);
        $utilized = new Money(0, $currency);
        $allocatedCampaigns = 0;

        foreach ($campaigns as $campaign) {
            /** @var array<string, mixed> $financial */
            $financial = $campaign->getAttribute('financial_metrics');
            $utilized = $utilized->add(Money::fromMajor((string) $financial['utilized'], $currency));

            if ($campaign->lifecycleStatus() !== CampaignStatus::COMPLETED) {
                $campaignReserved = Money::fromMajor((string) $financial['reserved'], $currency);
                $reserved = $reserved->add($campaignReserved);

                if ($campaignReserved->amountInMinorUnits > 0) {
                    $allocatedCampaigns++;
                }
            }
        }
        $totalFunded = Payment::query()
            ->whereBelongsTo($workspace)
            ->where('purpose', PaymentPurpose::WALLET_TOP_UP)
            ->where('status', PaymentStatus::SUCCEEDED)
            ->sum('amount_minor');
        $returned = Money::fromMajor(
            (string) $campaigns->sum(fn (Campaign $campaign): float => (float) $campaign->returned_amount),
            $currency,
        );
        [$transactions, $transactionCount] = $this->ledger($workspace);

        return new InstitutionalFundingPageData(
            summary: [
                'currency' => $currency,
                'availableBalance' => $available->toMajorAmount(),
                'allocatedBalance' => $reserved->toMajorAmount(),
                'utilized' => $utilized->toMajorAmount(),
                'totalFunded' => (new Money((int) $totalFunded, $currency))->toMajorAmount(),
                'walletBalance' => $available->add($reserved)->toMajorAmount(),
                'allocatedCampaigns' => $allocatedCampaigns,
            ],
            program: [
                'name' => $program->name,
                'startsOn' => $program->starts_on->toDateString(),
                'endsOn' => $program->ends_on->toDateString(),
                'status' => now()->startOfDay()->greaterThan($program->ends_on) ? 'ended' : 'active',
                'statusLabel' => now()->startOfDay()->greaterThan($program->ends_on) ? 'Ended' : 'Active',
                'coverageType' => $program->coverage_type->value,
                'coverageTypeLabel' => $program->coverage_type->label(),
                'gpLimitPerBeneficiary' => $program->gp_limit_per_beneficiary,
                'specialistLimitPerBeneficiary' => $program->specialist_limit_per_beneficiary,
                'dailyConsultationLimit' => $program->daily_consultation_limit,
                'expiryCadence' => $program->expiry_cadence->value,
                'expiryCadenceLabel' => $program->expiry_cadence->label(),
                'paymentPreference' => $program->payment_preference->value,
                'paymentPreferenceLabel' => $program->payment_preference->label(),
            ],
            campaigns: $this->campaignAllocations($campaigns),
            returnedFromEndedCampaigns: $returned->toMajorAmount(),
            transactions: $transactions,
            transactionCount: $transactionCount,
            configuration: [
                'coverageTypes' => [
                    ['value' => 'shared_pool', 'label' => 'Shared Coverage Pool'],
                    ['value' => 'per_beneficiary', 'label' => 'Per-Beneficiary Coverage'],
                ],
                'expiryCadences' => [['value' => 'annual', 'label' => 'Annual']],
                'paymentPreferences' => [
                    ['value' => 'user_choice', 'label' => 'User choice'],
                    ['value' => 'beneficiary_wallet', 'label' => 'Beneficiary wallet'],
                    ['value' => 'card_payment', 'label' => 'Card payment'],
                ],
                'fundingMethods' => [['value' => 'bank_transfer', 'label' => 'Bank transfer']],
            ],
        );
    }

    /**
     * @param  EloquentCollection<int, Campaign>  $campaigns
     * @return list<array<string, mixed>>
     */
    private function campaignAllocations(EloquentCollection $campaigns): array
    {
        return array_values($campaigns->map(function (Campaign $campaign): array {
            /** @var array<string, mixed> $financial */
            $financial = $campaign->getAttribute('financial_metrics');
            $status = $campaign->lifecycleStatus();

            return [
                'id' => (int) $campaign->getKey(),
                'name' => $campaign->name,
                'slug' => $campaign->slug,
                'location' => $campaign->location,
                'status' => $status->value,
                'statusLabel' => $status->label(),
                'allocated' => (string) $financial['allocated'],
                'utilized' => (string) $financial['utilized'],
                'reserved' => (string) $financial['reserved'],
                'returned' => (string) $campaign->returned_amount,
                'ended' => $status === CampaignStatus::COMPLETED,
            ];
        })->all());
    }

    /** @return array{0: list<array<string, mixed>>, 1: int} */
    private function ledger(Workspace $workspace): array
    {
        $types = [
            TransactionTypes::TOPUP,
            TransactionTypes::CAMPAIGN_BOOTH_SETUP,
            TransactionTypes::CAMPAIGN_BOOTH_SERVICE,
            TransactionTypes::CAMPAIGN_OPERATING_COST,
        ];
        $transactionQuery = Transaction::query()
            ->where('owner_type', $workspace->getMorphClass())
            ->where('owner_id', $workspace->getKey())
            ->where('status', TransactionStatus::COMPLETED)
            ->whereIn('type', $types);
        $usageQuery = CampaignUsageEntry::query()->where('workspace_id', $workspace->getKey());
        $count = (clone $transactionQuery)->count() + (clone $usageQuery)->count();

        $ledgerTransactions = (clone $transactionQuery)
            ->with('payment:id,metadata')
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (Transaction $transaction): array => [
                'id' => 'transaction-'.$transaction->getKey(),
                'date' => $transaction->created_at?->toDateString(),
                'timestamp' => $transaction->created_at?->getTimestamp() ?? 0,
                'type' => $transaction->type->value,
                'typeLabel' => $this->transactionLabel($transaction->type),
                'description' => $this->transactionDescription($transaction),
                'beneficiary' => null,
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'flow' => $transaction->flow->value,
            ]);
        $usageEntries = (clone $usageQuery)
            ->with(['campaign:id,name', 'workspaceBeneficiary:id,first_name,last_name'])
            ->latest('occurred_at')
            ->limit(50)
            ->get()
            ->map(fn (CampaignUsageEntry $entry): array => [
                'id' => 'usage-'.$entry->getKey(),
                'date' => $entry->occurred_at->toDateString(),
                'timestamp' => $entry->occurred_at->getTimestamp(),
                'type' => $entry->benefit->value,
                'typeLabel' => $entry->benefit->label(),
                'description' => $entry->benefit->label().' — '.$entry->campaign->name,
                'beneficiary' => $entry->workspaceBeneficiary === null
                    ? null
                    : trim($entry->workspaceBeneficiary->first_name.' '.$entry->workspaceBeneficiary->last_name),
                'amount' => $entry->total_amount,
                'currency' => $entry->currency,
                'flow' => TransactionFlow::DEBIT->value,
            ]);

        return [
            array_values($ledgerTransactions
                ->concat($usageEntries)
                ->sortByDesc('timestamp')
                ->take(50)
                ->map(function (array $entry): array {
                    unset($entry['timestamp']);

                    return $entry;
                })
                ->all()),
            $count,
        ];
    }

    private function transactionLabel(TransactionTypes $type): string
    {
        return match ($type) {
            TransactionTypes::TOPUP => 'Funding',
            TransactionTypes::CAMPAIGN_BOOTH_SETUP => 'Booth setup',
            TransactionTypes::CAMPAIGN_BOOTH_SERVICE => 'Booth service',
            TransactionTypes::CAMPAIGN_OPERATING_COST => 'Operating cost',
            default => 'Transaction',
        };
    }

    private function transactionDescription(Transaction $transaction): string
    {
        if ($transaction->type === TransactionTypes::TOPUP) {
            $method = (string) ($transaction->payment?->metadata['funding_method'] ?? '');

            return $method === 'bank_transfer' ? 'Wallet top-up (Bank transfer)' : 'Wallet top-up';
        }

        return (string) ($transaction->meta['description'] ?? $this->transactionLabel($transaction->type));
    }
}
