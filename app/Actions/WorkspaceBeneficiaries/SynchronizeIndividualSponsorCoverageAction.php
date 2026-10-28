<?php

namespace App\Actions\WorkspaceBeneficiaries;

use App\Enums\AccountTypes;
use App\Enums\WorkspaceBeneficiaries\WorkspaceBeneficiaryStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Workspace;
use App\Models\WorkspaceBeneficiary;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Revoltify\Subscriptionify\Enums\SubscriptionStatus;

final readonly class SynchronizeIndividualSponsorCoverageAction
{
    public function __construct(
        private EnsureIndividualSponsorCoverageAction $ensureCoverage,
    ) {}

    public function execute(Workspace $workspace, Plan $plan): ?WorkspaceBeneficiary
    {
        if ($workspace->type !== AccountTypes::INDIVIDUAL
            || $plan->account_type !== AccountTypes::INDIVIDUAL) {
            return null;
        }

        if ($plan->covers_sponsor_by_default) {
            return $this->ensureCoverage->execute($workspace);
        }

        return DB::transaction(function () use ($workspace): ?WorkspaceBeneficiary {
            $lockedWorkspace = Workspace::query()
                ->whereKey($workspace->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $coverage = WorkspaceBeneficiary::query()
                ->whereBelongsTo($lockedWorkspace)
                ->whereNotNull('primary_sponsor_user_id')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if (! $coverage instanceof WorkspaceBeneficiary) {
                return null;
            }

            $coverage->update([
                'primary_sponsor_user_id' => null,
                'status' => WorkspaceBeneficiaryStatus::Revoked,
                'revoked_at' => now(),
            ]);

            return $coverage->refresh();
        });
    }

    public function executeForPlan(Plan $plan): void
    {
        if ($plan->account_type !== AccountTypes::INDIVIDUAL) {
            return;
        }

        $workspaceMorph = (new Workspace)->getMorphClass();
        $latestUsableIds = Subscription::query()
            ->selectRaw('MAX(id)')
            ->where('subscribable_type', $workspaceMorph)
            ->where('starts_at', '<=', now())
            ->where(function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query->where('status', SubscriptionStatus::Active)
                        ->where(function (Builder $query): void {
                            $query->whereNull('ends_at')->orWhere('ends_at', '>', now());
                        });
                })->orWhere(function (Builder $query): void {
                    $query->where('status', SubscriptionStatus::Trialing)
                        ->where('trial_ends_at', '>', now());
                });
            })
            ->groupBy('subscribable_id');

        Subscription::query()
            ->whereIn('id', $latestUsableIds)
            ->where('plan_id', $plan->getKey())
            ->orderBy('id')
            ->eachById(function (Subscription $subscription) use ($plan): void {
                $workspace = Workspace::query()->find($subscription->subscribable_id);

                if ($workspace instanceof Workspace) {
                    $this->execute($workspace, $plan);
                }
            }, 100);
    }
}
