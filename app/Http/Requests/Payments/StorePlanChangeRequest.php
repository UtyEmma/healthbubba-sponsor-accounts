<?php

namespace App\Http\Requests\Payments;

use App\Enums\Payments\SubscriptionPaymentSource;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Workspace;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Validation\Rule;

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
            'payment_source' => ['required', Rule::enum(SubscriptionPaymentSource::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('payment_source')) {
            $this->merge(['payment_source' => SubscriptionPaymentSource::WALLET->value]);
        }
    }

    public function paymentSource(): SubscriptionPaymentSource
    {
        return SubscriptionPaymentSource::from((string) $this->validated('payment_source'));
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
