<?php

namespace App\DTOs\Funding;

final readonly class InstitutionalFundingPageData
{
    /**
     * @param  array<string, mixed>  $summary
     * @param  array<string, mixed>  $program
     * @param  list<array<string, mixed>>  $campaigns
     * @param  list<array<string, mixed>>  $transactions
     * @param  array<string, mixed>  $configuration
     */
    public function __construct(
        public array $summary,
        public array $program,
        public array $campaigns,
        public string $returnedFromEndedCampaigns,
        public array $transactions,
        public int $transactionCount,
        public array $configuration,
    ) {}
}
