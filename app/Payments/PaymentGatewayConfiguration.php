<?php

namespace App\Payments;

use App\Enums\Payments\PaymentGatewayName;

final class PaymentGatewayConfiguration
{
    /** @return list<string> */
    public function issues(PaymentGatewayName $gateway): array
    {
        return match ($gateway) {
            PaymentGatewayName::PAYSTACK => $this->missing([
                'PAYSTACK_SECRET_KEY' => config('payments.gateways.paystack.secret_key'),
            ]),
            PaymentGatewayName::STRIPE => $this->missing([
                'STRIPE_SECRET_KEY' => config('payments.gateways.stripe.secret_key'),
                'STRIPE_WEBHOOK_SECRET' => config('payments.gateways.stripe.webhook_secret'),
            ]),
        };
    }

    public function configured(PaymentGatewayName $gateway): bool
    {
        return $this->issues($gateway) === [];
    }

    /** @param array<string, mixed> $values
     * @return list<string>
     */
    private function missing(array $values): array
    {
        $missing = [];

        foreach ($values as $name => $value) {
            if (! is_string($value) || trim($value) === '') {
                $missing[] = "{$name} is not configured.";
            }
        }

        return $missing;
    }
}
