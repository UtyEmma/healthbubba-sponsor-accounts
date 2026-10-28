<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Queries\Admin\AdminMonitoringQuery;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class AdminAttentionStats extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

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
            Stat::make('Subscriptions needing attention', number_format($data->expiringOrPastDueSubscriptions))
                ->description('Past due or ending within 30 days')
                ->color($data->expiringOrPastDueSubscriptions > 0 ? 'warning' : 'success'),
            Stat::make('Booth billing attention', number_format($data->delinquentBooths))
                ->description('Grace-period or suspended booths')
                ->color($data->delinquentBooths > 0 ? 'danger' : 'success'),
            Stat::make('Empty active wallets', number_format($data->insufficientWallets))
                ->description('Active or past-due subscriptions with no balance')
                ->color($data->insufficientWallets > 0 ? 'warning' : 'success'),
            Stat::make('Campaigns ending soon', number_format($data->campaignsEndingSoon))
                ->description('Ending within 30 days')
                ->color($data->campaignsEndingSoon > 0 ? 'warning' : 'success'),
        ];
    }
}
