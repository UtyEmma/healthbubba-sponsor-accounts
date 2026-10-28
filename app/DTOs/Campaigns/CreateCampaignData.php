<?php

namespace App\DTOs\Campaigns;

use App\Enums\InstitutionalCoverageExpiry;
use App\Enums\InstitutionalCoverageType;
use App\Enums\InstitutionalPaymentPreference;
use App\Models\User;
use App\Models\Workspace;

final readonly class CreateCampaignData
{
    public function __construct(
        public Workspace $workspace,
        public User $user,
        public CampaignDetailsData $details,
        public CampaignEnrollmentData $enrollment,
        public CampaignHealthcareAllocationData $healthcare,
        public CampaignBoothData $booth,
        public ?InstitutionalCoverageType $coverageTypeOverride = null,
        public ?int $scheduledLimitOverride = null,
        public ?int $instantLimitOverride = null,
        public ?int $dailyLimitOverride = null,
        public ?InstitutionalCoverageExpiry $coverageExpiryOverride = null,
        public ?InstitutionalPaymentPreference $paymentPreferenceOverride = null,
    ) {}
}
