<?php

namespace App\Payments;

use App\Enums\Payments\PaymentGatewayName;
use App\Models\PaymentGatewaySetting;
use Illuminate\Support\Facades\Cache;

final class ActivePaymentGatewayResolver
{
    public const string CACHE_KEY = 'payments.active_gateway';

    public function resolve(): PaymentGatewayName
    {
        $value = Cache::rememberForever(self::CACHE_KEY, function (): string {
            $setting = PaymentGatewaySetting::query()->find(PaymentGatewaySetting::SINGLETON_ID);

            return $setting?->active_gateway->value ?? $this->configuredFallback()->value;
        });

        return $this->fromCachedValue($value);
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function configuredFallback(): PaymentGatewayName
    {
        return PaymentGatewayName::tryFrom(config()->string('payments.default'))
            ?? PaymentGatewayName::PAYSTACK;
    }

    private function fromCachedValue(mixed $value): PaymentGatewayName
    {
        if (! is_string($value)) {
            return $this->configuredFallback();
        }

        return PaymentGatewayName::tryFrom($value) ?? $this->configuredFallback();
    }
}
