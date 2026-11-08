<?php

namespace App\Http\Requests\Payments;

use App\Enums\AccountTypes;
use App\Enums\Subscriptions\IndividualPlanBillingCycle;
use App\Models\Subscription;
use App\Models\Workspace;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class StoreSubscriptionBillingCycleRequest extends AuthorizedWorkspacePaymentRequest
{
    public function authorize(): bool
    {
        if (! parent::authorize()) {
            return false;
        }

        $workspace = Workspace::current();
        $subscription = $this->route('subscription');

        return $workspace instanceof Workspace
            && $workspace->type === AccountTypes::INDIVIDUAL
            && $subscription instanceof Subscription
            && $subscription->subscribable_type === $workspace->getMorphClass()
            && (int) $subscription->subscribable_id === (int) $workspace->getKey();
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'billing_cycle' => ['required', Rule::enum(IndividualPlanBillingCycle::class)],
        ];
    }

    public function subscription(): Subscription
    {
        $subscription = $this->route('subscription');

        return $subscription instanceof Subscription
            ? $subscription
            : throw new NotFoundHttpException('The selected subscription was not found.');
    }

    public function billingCycle(): IndividualPlanBillingCycle
    {
        return IndividualPlanBillingCycle::from((string) $this->validated('billing_cycle'));
    }
}
