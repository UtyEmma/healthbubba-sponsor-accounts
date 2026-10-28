<?php

namespace App\Queries\Admin;

use App\DTOs\Admin\AdminMonitoringData;
use App\Enums\CampaignBoothStatus;
use App\Enums\CampaignStatus;
use App\Enums\Subscriptions\SubscriptionFundingSource;
use App\Enums\Transactions\TransactionFlow;
use App\Enums\Transactions\TransactionStatus;
use App\Enums\Transactions\TransactionTypes;
use App\Models\Campaign;
use App\Models\CampaignBooth;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Workspace;
use Illuminate\Support\Collection;
use Revoltify\Subscriptionify\Enums\SubscriptionStatus;

final readonly class AdminMonitoringQuery
{
    public function execute(): AdminMonitoringData
    {
        return new AdminMonitoringData(
            totalUsers: User::query()->count(),
            totalWorkspaces: Workspace::query()->count(),
            workspacesByType: $this->groupedCounts(Workspace::query()->selectRaw('type, COUNT(*) aggregate')->groupBy('type')->pluck('aggregate', 'type')),
            subscriptionsByStatus: $this->groupedCounts(Subscription::query()->selectRaw('status, COUNT(*) aggregate')->groupBy('status')->pluck('aggregate', 'status')),
            complimentarySubscriptions: Subscription::query()
                ->where('funding_source', SubscriptionFundingSource::Complimentary)
                ->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::Trialing])
                ->count(),
            campaignsByStatus: $this->groupedCounts(Campaign::query()->selectRaw('status, COUNT(*) aggregate')->groupBy('status')->pluck('aggregate', 'status')),
            adminRecordedSponsorFunding: (string) Transaction::query()
                ->where('type', TransactionTypes::TOPUP)
                ->where('flow', TransactionFlow::CREDIT)
                ->where('status', TransactionStatus::COMPLETED)
                ->where('meta->recording_channel', 'admin')
                ->sum('amount'),
            expiringOrPastDueSubscriptions: Subscription::query()
                ->where(function ($query): void {
                    $query->where('status', SubscriptionStatus::PastDue)
                        ->orWhere(function ($query): void {
                            $query->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::Trialing])
                                ->whereBetween('ends_at', [now(), now()->addDays(30)]);
                        });
                })->count(),
            delinquentBooths: CampaignBooth::query()
                ->whereIn('status', [CampaignBoothStatus::GracePeriod, CampaignBoothStatus::Suspended])
                ->count(),
            insufficientWallets: Wallet::query()
                ->where('owner_type', (new Workspace)->getMorphClass())
                ->where('balance', '<=', 0)
                ->whereExists(function ($query): void {
                    $query->selectRaw('1')
                        ->from('subscriptions')
                        ->whereColumn('subscriptions.subscribable_id', 'wallets.owner_id')
                        ->where('subscriptions.subscribable_type', (new Workspace)->getMorphClass())
                        ->whereIn('subscriptions.status', [SubscriptionStatus::Active->value, SubscriptionStatus::PastDue->value]);
                })->count(),
            campaignsEndingSoon: Campaign::query()
                ->whereIn('status', [CampaignStatus::PENDING, CampaignStatus::IN_PROGRESS, CampaignStatus::PAUSED])
                ->whereBetween('end_date', [today(), today()->addDays(30)])
                ->count(),
        );
    }

    /** @param Collection<string, int|string> $counts
     * @return array<string, int>
     */
    private function groupedCounts(Collection $counts): array
    {
        return $counts->map(fn (int|string $count): int => (int) $count)->all();
    }
}
