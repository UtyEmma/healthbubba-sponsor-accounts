<?php

namespace App\Services\WorkspaceBeneficiaries;

use App\DTOs\WorkspaceBeneficiaries\CapacitySummary;
use App\Enums\AccountTypes;
use App\Enums\WorkspaceBeneficiaries\WorkspaceBeneficiaryStatus;
use App\Enums\WorkspaceMembers\WorkspaceMemberRole;
use App\Enums\WorkspaceMembers\WorkspaceMemberStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Revoltify\Subscriptionify\Enums\SubscriptionStatus;

final class WorkspaceBeneficiaryCapacityService
{
    public function summary(Workspace $workspace): CapacitySummary
    {
        $this->expirePending($workspace);
        $subscription = $this->query($workspace)->first();
        $used = $workspace->beneficiaryEnrollments()->consumingCapacity()->count();

        if (! $subscription instanceof Subscription || ! $this->hasUsableTerm($subscription)) {
            return new CapacitySummary(
                used: $used,
                total: 0,
                unavailableReason: 'An active or trialing subscription is required.',
            );
        }

        return new CapacitySummary(
            used: $used,
            total: max(0, $subscription->capacity_count),
            sponsorCoveredByDefault: $subscription->plan->covers_sponsor_by_default,
        );
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
        return $workspace->beneficiaryEnrollments()->consumingCapacity()->count();
    }

    public function usedForPlan(Workspace $workspace, Plan $plan): int
    {
        $used = $this->used($workspace);

        if ($workspace->type !== AccountTypes::INDIVIDUAL
            || ! $plan->covers_sponsor_by_default) {
            return $used;
        }

        $ownerEmail = WorkspaceMember::query()
            ->whereBelongsTo($workspace)
            ->where('role', WorkspaceMemberRole::Owner)
            ->where('status', WorkspaceMemberStatus::Active)
            ->whereNotNull('user_id')
            ->with('user:id,email')
            ->orderBy('id')
            ->first()?->user?->email;

        if (! is_string($ownerEmail) || trim($ownerEmail) === '') {
            return $used;
        }

        $ownerConsumesCapacity = $workspace->beneficiaryEnrollments()
            ->consumingCapacity()
            ->whereRaw('LOWER(email) = ?', [mb_strtolower(trim($ownerEmail))])
            ->exists();

        return max(0, $used - (int) $ownerConsumesCapacity);
    }

    public function expirePending(Workspace $workspace): int
    {
        return $workspace->beneficiaryEnrollments()
            ->where('status', WorkspaceBeneficiaryStatus::Pending)
            ->where('expires_at', '<=', now())
            ->update(['status' => WorkspaceBeneficiaryStatus::Expired]);
    }

    /** @return Builder<Subscription> */
    private function query(Workspace $workspace): Builder
    {
        return Subscription::query()
            ->with('plan')
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
