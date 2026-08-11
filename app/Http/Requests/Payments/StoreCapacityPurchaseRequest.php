<?php

namespace App\Http\Requests\Payments;

use App\Enums\CapacityPurchases\CapacityPaymentSource;
use App\Models\Subscription;
use App\Models\Workspace;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class StoreCapacityPurchaseRequest extends AuthorizedWorkspacePaymentRequest
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

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'payment_source' => ['required', Rule::enum(CapacityPaymentSource::class)],
        ];
    }

    public function subscription(): Subscription
    {
        $subscription = $this->route('subscription');

        return $subscription instanceof Subscription
            ? $subscription
            : throw new NotFoundHttpException('The selected subscription was not found.');
    }

    public function quantity(): int
    {
        return (int) $this->validated('quantity');
    }

    public function paymentSource(): CapacityPaymentSource
    {
        return CapacityPaymentSource::from((string) $this->validated('payment_source'));
    }
}
