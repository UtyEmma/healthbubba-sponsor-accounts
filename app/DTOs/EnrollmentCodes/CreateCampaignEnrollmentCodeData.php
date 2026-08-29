<?php

namespace App\DTOs\EnrollmentCodes;

use App\Models\Campaign;
use App\Models\User;
use Carbon\CarbonImmutable;

final readonly class CreateCampaignEnrollmentCodeData
{
    public function __construct(
        public Campaign $campaign,
        public User $creator,
        public int $enrollmentLimit,
        public CarbonImmutable $expiresAt,
    ) {}
}
