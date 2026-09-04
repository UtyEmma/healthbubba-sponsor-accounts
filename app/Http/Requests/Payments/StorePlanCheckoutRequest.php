<?php

namespace App\Http\Requests\Payments;

use App\Enums\Payments\PaymentGatewayName;
use App\Enums\Payments\SubscriptionPaymentSource;
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
            'gateway' => ['nullable', Rule::enum(PaymentGatewayName::class)],
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

    public function gateway(): ?PaymentGatewayName
    {
        $gateway = $this->validated('gateway');

        return is_string($gateway) ? PaymentGatewayName::tryFrom($gateway) : null;
    }
}
