<?php

namespace App\DTOs\Campaigns;

final readonly class CampaignMonthlyDeductionResult
{
    public function __construct(
        public int $costsChecked,
        public int $chargesCompleted,
    ) {}
}
