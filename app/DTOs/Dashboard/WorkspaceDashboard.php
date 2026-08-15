<?php

namespace App\DTOs\Dashboard;

use App\Enums\AccountTypes;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

final readonly class WorkspaceDashboard
{
    /**
     * @param  array<string, mixed>  $coverage
     * @param  list<DepartmentUtilization>  $departmentUtilization
     * @param  list<ConsultationTrend>  $consultationTrends
     * @param  Collection<int, DatabaseNotification>  $recentActivities
     */
    public function __construct(
        public AccountTypes $accountType,
        public int $totalBeneficiaries,
        public int $activeBeneficiaries,
        public int $capacity,
        public string $walletBalance,
        public string $walletCurrency,
        public string $totalFunded,
        public ?DashboardSubscription $subscription,
        public array $coverage,
        public int $completedConsultations,
        public array $departmentUtilization,
        public array $consultationTrends,
        public Collection $recentActivities,
    ) {}
}
