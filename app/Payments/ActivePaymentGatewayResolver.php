<?php

namespace App\Payments;

use App\Enums\AccountTypes;
use App\Enums\Payments\PaymentGatewayName;
use App\Models\PaymentGatewaySetting;
use Illuminate\Support\Facades\Cache;

final class ActivePaymentGatewayResolver
{
    public const string CACHE_KEY_PREFIX = 'payments.active_gateway.';

    public function resolve(AccountTypes $accountType): PaymentGatewayName
    {
        $value = Cache::rememberForever($this->cacheKey($accountType), function () use ($accountType): string {
            $setting = PaymentGatewaySetting::query()->find(PaymentGatewaySetting::SINGLETON_ID);

            return $setting?->gatewayFor($accountType)->value ?? $this->configuredFallback()->value;
        });

        return $this->fromCachedValue($value);
    }

    public function forget(?AccountTypes $accountType = null): void
    {
        if ($accountType instanceof AccountTypes) {
            Cache::forget($this->cacheKey($accountType));

            return;
        }

        foreach (AccountTypes::cases() as $type) {
            Cache::forget($this->cacheKey($type));
        }
    }

    private function cacheKey(AccountTypes $accountType): string
    {
        return self::CACHE_KEY_PREFIX.$accountType->value;
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
