<?php

namespace App\Services\WorkspaceBeneficiaries;

use App\DTOs\WorkspaceBeneficiaries\CapacitySummary;
use App\Enums\WorkspaceBeneficiaries\WorkspaceBeneficiaryStatus;
use App\Models\Subscription;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Revoltify\Subscriptionify\Enums\SubscriptionStatus;

final class WorkspaceBeneficiaryCapacityService
{
    public function summary(Workspace $workspace): CapacitySummary
    {
        $this->expirePending($workspace);
        $subscription = $this->query($workspace)->first();
        $used = $workspace->workspaceBeneficiaries()->consumingCapacity()->count();

        if (! $subscription instanceof Subscription || ! $this->hasUsableTerm($subscription)) {
            return new CapacitySummary($used, 0, 'An active or trialing subscription is required.');
        }

        return new CapacitySummary($used, max(0, $subscription->capacity_count));
    }

    public function lockSubscription(Workspace $workspace): Subscription
    {
        $subscription = $this->query($workspace)->lockForUpdate()->first();

        if (! $subscription instanceof Subscription || ! $this->hasUsableTerm($subscription)) {
            throw ValidationException::withMessages([
                'subscription' => 'An active or trialing subscription is required to manage beneficiaries or employees.',
            ]);
        }

        return $subscription;
    }

    public function used(Workspace $workspace): int
    {
        return $workspace->workspaceBeneficiaries()->consumingCapacity()->count();
    }

    public function expirePending(Workspace $workspace): int
    {
        return $workspace->workspaceBeneficiaries()
            ->where('status', WorkspaceBeneficiaryStatus::Pending)
            ->where('expires_at', '<=', now())
            ->update(['status' => WorkspaceBeneficiaryStatus::Expired]);
    }

    /** @return Builder<Subscription> */
    private function query(Workspace $workspace): Builder
    {
        return Subscription::query()
            ->where('subscribable_type', $workspace->getMorphClass())
            ->where('subscribable_id', $workspace->getKey())
            ->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::Trialing])
            ->latest('id');
    }

    private function hasUsableTerm(Subscription $subscription): bool
    {
        if ($subscription->starts_at->isFuture()) {
            return false;
        }

        if ($subscription->status === SubscriptionStatus::Trialing) {
            return $subscription->trial_ends_at?->isFuture() === true;
        }

        return $subscription->ends_at === null || $subscription->ends_at->isFuture();
    }
}
