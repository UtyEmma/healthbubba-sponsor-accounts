<?php

namespace App\Queries\InstitutionalCampaigns;

use App\Enums\CampaignStatus;
use App\Enums\Transactions\TransactionTypes;
use App\Models\Campaign;
use App\Models\CampaignUsageEntry;
use App\Models\Transaction;
use App\Models\Workspace;
use Illuminate\Support\Collection;

final readonly class CampaignDetailQuery
{
    /** @return array<string, mixed> */
    public function get(Campaign $campaign, Workspace $workspace): array
    {
        $workspace->loadMissing('wallet');
        $campaign->load([
            'booths' => static fn ($query) => $query
                ->withCount('beneficiaries')
                ->with(['recurringCosts.charges'])
                ->orderBy('id'),
        ]);

        $usageEntries = CampaignUsageEntry::query()
            ->whereBelongsTo($campaign)
            ->with('workspaceBeneficiary')
            ->latest('occurred_at')
            ->get();
        $transactions = Transaction::query()
            ->whereMorphedTo('owner', $workspace)
            ->where('meta->campaign_id', $campaign->getKey())
            ->whereIn('type', [
                TransactionTypes::CAMPAIGN_ALLOCATION->value,
                TransactionTypes::CAMPAIGN_REFUND->value,
                TransactionTypes::CAMPAIGN_BOOTH_SETUP->value,
                TransactionTypes::CAMPAIGN_BOOTH_SERVICE->value,
                TransactionTypes::CAMPAIGN_OPERATING_COST->value,
            ])
            ->latest()
            ->get();

        return [
            'capabilities' => $this->capabilities($campaign->lifecycleStatus()),
            'counts' => [
                'enrollment' => (int) $campaign->getAttribute('beneficiaries_count'),
                'booths' => $campaign->booths->count(),
                'usage' => $usageEntries->count(),
            ],
            'configuration' => [
                'currency' => $campaign->currency,
                'walletBalance' => (string) $workspace->wallet->balance,
                'gpUnitFee' => (string) config('campaigns.default_gp_fee'),
                'specialistUnitFee' => (string) config('campaigns.default_specialist_fee'),
                'boothSetupUnitFee' => (string) config('campaigns.booth_setup_fee'),
                'boothMonthlyUnitFee' => (string) config('campaigns.booth_monthly_fee'),
            ],
            'enrollmentCode' => $campaign->display_enrollment_code,
            'booths' => $campaign->booths,
            'ledger' => $this->ledger($usageEntries, $transactions),
        ];
    }

    /** @return array<string, bool> */
    private function capabilities(CampaignStatus $status): array
    {
        return [
            'pause' => $status === CampaignStatus::IN_PROGRESS,
            'resume' => $status === CampaignStatus::PAUSED,
            'end' => $status !== CampaignStatus::COMPLETED,
            'allocate' => $status !== CampaignStatus::COMPLETED,
            'recordUsage' => $status === CampaignStatus::IN_PROGRESS,
            'enroll' => $status !== CampaignStatus::COMPLETED,
            'addBooths' => $status !== CampaignStatus::COMPLETED,
        ];
    }

    /**
     * @param  Collection<int, CampaignUsageEntry>  $usages
     * @param  Collection<int, Transaction>  $transactions
     * @return list<array<string, mixed>>
     */
    private function ledger(Collection $usages, Collection $transactions): array
    {
        $rows = collect();

        foreach ($usages as $usage) {
            $rows->push([
                'id' => 'usage-'.$usage->getKey(),
                'date' => $usage->occurred_at->toDateString(),
                'type' => 'utilization',
                'label' => 'Utilization',
                'benefit' => $usage->benefit->label(),
                'beneficiary' => $usage->workspaceBeneficiary?->first_name === null
                    ? null
                    : trim($usage->workspaceBeneficiary->first_name.' '.$usage->workspaceBeneficiary->last_name),
                'quantity' => $usage->quantity,
                'amount' => '-'.$usage->total_amount,
                'sortAt' => $usage->occurred_at->timestamp,
            ]);
        }

        foreach ($transactions as $transaction) {
            $isCredit = in_array($transaction->type, [
                TransactionTypes::CAMPAIGN_ALLOCATION,
                TransactionTypes::CAMPAIGN_REFUND,
            ], true);
            $rows->push([
                'id' => 'transaction-'.$transaction->getKey(),
                'date' => $transaction->created_at?->toDateString(),
                'type' => $transaction->type->value,
                'label' => match ($transaction->type) {
                    TransactionTypes::CAMPAIGN_ALLOCATION => 'Allocation',
                    TransactionTypes::CAMPAIGN_REFUND => 'Refund',
                    TransactionTypes::CAMPAIGN_BOOTH_SETUP => 'Booth setup',
                    TransactionTypes::CAMPAIGN_BOOTH_SERVICE => 'Booth service',
                    TransactionTypes::CAMPAIGN_OPERATING_COST => 'Operating cost',
                    default => $transaction->type->value,
                },
                'benefit' => data_get($transaction->meta, 'description', 'All benefits'),
                'beneficiary' => null,
                'quantity' => data_get($transaction->meta, 'quantity'),
                'amount' => ($isCredit ? '+' : '-').$transaction->amount,
                'sortAt' => $transaction->created_at->timestamp,
            ]);
        }

        return array_values($rows->sortByDesc('sortAt')->values()->map(function (array $row): array {
            unset($row['sortAt']);

            return $row;
        })->all());
    }
}
