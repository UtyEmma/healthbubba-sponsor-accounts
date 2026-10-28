<?php

namespace App\DTOs\Admin;

final readonly class AdminMonitoringData
{
    /**
     * @param  array<string, int>  $workspacesByType
     * @param  array<string, int>  $subscriptionsByStatus
     * @param  array<string, int>  $campaignsByStatus
     */
    public function __construct(
        public int $totalUsers,
        public int $totalWorkspaces,
        public array $workspacesByType,
        public array $subscriptionsByStatus,
        public int $complimentarySubscriptions,
        public array $campaignsByStatus,
        public string $adminRecordedSponsorFunding,
        public int $expiringOrPastDueSubscriptions,
        public int $delinquentBooths,
        public int $insufficientWallets,
        public int $campaignsEndingSoon,
    ) {}
}
