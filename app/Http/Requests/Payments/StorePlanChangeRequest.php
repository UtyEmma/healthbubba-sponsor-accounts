<?php

namespace App\Http\Requests\Payments;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Workspace;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class StorePlanChangeRequest extends AuthorizedWorkspacePaymentRequest
{
    public function authorize(): bool
    {
        if (! parent::authorize()) {
            return false;
        }

        $workspace = Workspace::current();
        $subscription = $this->route('subscription');

        return $workspace instanceof Workspace
            && $subscription instanceof Subscription
            && $subscription->subscribable_type === $workspace->getMorphClass()
            && (int) $subscription->subscribable_id === (int) $workspace->getKey();
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'confirmed' => ['required', 'accepted'],
        ];
    }

    public function subscription(): Subscription
    {
        $subscription = $this->route('subscription');

        return $subscription instanceof Subscription
            ? $subscription
            : throw new NotFoundHttpException('The selected subscription was not found.');
    }

    public function targetPlan(): Plan
    {
        $plan = $this->route('plan');

        return $plan instanceof Plan
            ? $plan
            : throw new NotFoundHttpException('The selected plan was not found.');
    }
}
