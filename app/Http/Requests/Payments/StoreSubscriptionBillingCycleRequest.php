<?php

namespace App\Http\Requests\Payments;

use App\Enums\AccountTypes;
use App\Enums\Payments\SubscriptionPaymentSource;
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
        $subscription = Subscription::find($this->route('subscription'));
        
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
            'payment_source' => ['required', Rule::enum(SubscriptionPaymentSource::class)],
            'confirmed' => ['required', 'accepted'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('payment_source')) {
            $this->merge(['payment_source' => SubscriptionPaymentSource::WALLET->value]);
        }
    }

    public function subscription(): Subscription
    {
        $subscription = Subscription::find($this->route('subscription'));

        return $subscription instanceof Subscription
            ? $subscription
            : throw new NotFoundHttpException('The selected subscription was not found.');
    }

    public function billingCycle(): IndividualPlanBillingCycle
    {
        return IndividualPlanBillingCycle::from((string) $this->validated('billing_cycle'));
    }

    public function paymentSource(): SubscriptionPaymentSource
    {
        return SubscriptionPaymentSource::from((string) $this->validated('payment_source'));
    }
}
