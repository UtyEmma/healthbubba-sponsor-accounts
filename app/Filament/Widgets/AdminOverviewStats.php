<?php

namespace App\Filament\Widgets;

use App\Enums\AccountTypes;
use App\Enums\CampaignStatus;
use App\Models\User;
use App\Queries\Admin\AdminMonitoringQuery;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Revoltify\Subscriptionify\Enums\SubscriptionStatus;

final class AdminOverviewStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user() instanceof User && auth()->user()->isAdmin();
    }

    /** @return list<Stat> */
    protected function getStats(): array
    {
        $data = app(AdminMonitoringQuery::class)->execute();

        return [
            Stat::make('Users', number_format($data->totalUsers))
                ->description('All registered identities')
                ->icon('heroicon-o-users'),
            Stat::make('Workspaces', number_format($data->totalWorkspaces))
                ->description(sprintf(
                    '%d individual · %d business · %d institutional',
                    $data->workspacesByType[AccountTypes::INDIVIDUAL->value] ?? 0,
                    $data->workspacesByType[AccountTypes::BUSINESS->value] ?? 0,
                    $data->workspacesByType[AccountTypes::INSTITUTION->value] ?? 0,
                ))
                ->icon('heroicon-o-building-office-2'),
            Stat::make('Active subscriptions', number_format($data->subscriptionsByStatus[SubscriptionStatus::Active->value] ?? 0))
                ->description(sprintf(
                    '%d past due · %d expired · %d complimentary',
                    $data->subscriptionsByStatus[SubscriptionStatus::PastDue->value] ?? 0,
                    $data->subscriptionsByStatus[SubscriptionStatus::Expired->value] ?? 0,
                    $data->complimentarySubscriptions,
                ))
                ->color('success')
                ->icon('heroicon-o-receipt-percent'),
            Stat::make('Campaigns', number_format(array_sum($data->campaignsByStatus)))
                ->description(sprintf(
                    '%d active · %d upcoming · %d paused · %d ended',
                    $data->campaignsByStatus[CampaignStatus::IN_PROGRESS->value] ?? 0,
                    $data->campaignsByStatus[CampaignStatus::PENDING->value] ?? 0,
                    $data->campaignsByStatus[CampaignStatus::PAUSED->value] ?? 0,
                    $data->campaignsByStatus[CampaignStatus::COMPLETED->value] ?? 0,
                ))
                ->icon('heroicon-o-megaphone'),
            Stat::make('Admin-recorded sponsor funding', '₦'.number_format((float) $data->adminRecordedSponsorFunding, 2))
                ->description('Sponsor-paid wallet credits entered by administrators')
                ->color('success')
                ->icon('heroicon-o-banknotes'),
        ];
    }
}
