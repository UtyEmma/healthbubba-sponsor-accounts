<?php

namespace App\Http\Requests\Payments;

use App\Enums\AccountTypes;
use App\Enums\Payments\SubscriptionPaymentSource;
use App\Enums\Subscriptions\IndividualPlanBillingCycle;
use App\Models\Plan;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class StorePlanCheckoutRequest extends AuthorizedWorkspacePaymentRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'additional_capacity' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'recurring_consent' => ['required', 'accepted'],
            'payment_source' => ['required', Rule::enum(SubscriptionPaymentSource::class)],
            'billing_cycle' => [
                Rule::requiredIf(fn (): bool => $this->workspace()->type === AccountTypes::INDIVIDUAL),
                'nullable',
                Rule::enum(IndividualPlanBillingCycle::class),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('payment_source')) {
            $this->merge(['payment_source' => SubscriptionPaymentSource::WALLET->value]);
        }

        if (! $this->has('additional_capacity') && $this->has('additional_seats')) {
            $this->merge(['additional_capacity' => $this->input('additional_seats')]);
        }

        if (! $this->has('billing_cycle') && $this->workspace()->type === AccountTypes::INDIVIDUAL) {
            $this->merge(['billing_cycle' => IndividualPlanBillingCycle::MONTHLY->value]);
        }
    }

    public function paymentSource(): SubscriptionPaymentSource
    {
        return SubscriptionPaymentSource::from((string) $this->validated('payment_source'));
    }

    public function plan(): Plan
    {
        $plan = $this->route('plan');

        return $plan instanceof Plan
            ? $plan
            : throw new NotFoundHttpException('The selected plan was not found.');
    }

    public function additionalCapacity(): int
    {
        return (int) ($this->validated('additional_capacity') ?? 0);
    }

    public function billingCycle(): ?IndividualPlanBillingCycle
    {
        if ($this->workspace()->type !== AccountTypes::INDIVIDUAL) {
            return null;
        }

        $cycle = $this->validated('billing_cycle');

        return is_string($cycle) ? IndividualPlanBillingCycle::from($cycle) : null;
    }
}
