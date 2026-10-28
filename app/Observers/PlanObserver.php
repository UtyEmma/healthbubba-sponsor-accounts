<?php

namespace App\Observers;

use App\Actions\WorkspaceBeneficiaries\SynchronizeIndividualSponsorCoverageAction;
use App\Models\Plan;

final readonly class PlanObserver
{
    public function __construct(
        private SynchronizeIndividualSponsorCoverageAction $synchronizeSponsorCoverage,
    ) {}

    public function updated(Plan $plan): void
    {
        if ($plan->wasChanged('covers_sponsor_by_default')) {
            $this->synchronizeSponsorCoverage->executeForPlan($plan);
        }
    }
}
