<?php

namespace App\DTOs\Dashboard;

final readonly class InstitutionalDashboard
{
    /**
     * @param  array<string, mixed>  $funding
     * @param  array<string, int>  $beneficiaries
     * @param  array<string, mixed>  $booths
     * @param  list<array<string, mixed>>  $campaignPerformance
     * @param  array<string, mixed>  $consultations
     * @param  list<array{month: string, consultations: int}>  $consultationTrends
     * @param  list<array<string, mixed>>  $activities
     * @param  list<array<string, mixed>>  $remainingCampaigns
     */
    public function __construct(
        public array $funding,
        public array $beneficiaries,
        public array $booths,
        public array $campaignPerformance,
        public array $consultations,
        public array $consultationTrends,
        public array $activities,
        public array $remainingCampaigns,
    ) {}
}
