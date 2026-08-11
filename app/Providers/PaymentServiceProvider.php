<?php

namespace App\Providers;

use App\Enums\Payments\PaymentGatewayName;
use App\Exceptions\Payments\GatewayConfigurationException;
use App\Payments\Gateways\PaystackGateway;
use App\Payments\PaymentGatewayRegistry;
use App\Payments\PaymentService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

final class PaymentServiceProvider extends ServiceProvider {
    private const string GATEWAY_TAG = 'payments.gateways';

    public function register(): void
    {
        $this->app->singleton(PaystackGateway::class, static fn (): PaystackGateway => new PaystackGateway(
            secretKey: (string) config('payments.gateways.paystack.secret_key', ''),
            baseUrl: config()->string('payments.gateways.paystack.base_url'),
            timeoutSeconds: config()->integer('payments.gateways.paystack.timeout'),
            connectTimeoutSeconds: config()->integer('payments.gateways.paystack.connect_timeout'),
            verifyAttempts: config()->integer('payments.gateways.paystack.verify_attempts'),
            retryDelayMilliseconds: config()->integer('payments.gateways.paystack.retry_delay'),
        ));

        $this->app->tag(PaystackGateway::class, self::GATEWAY_TAG);

        $this->app->singleton(PaymentGatewayRegistry::class, function (Application $app): PaymentGatewayRegistry {
            $configuredDefault = config()->string('payments.default');
            $defaultGateway = PaymentGatewayName::tryFrom($configuredDefault)
                ?? throw new GatewayConfigurationException("The default [{$configuredDefault}] payment gateway is invalid.");

            return new PaymentGatewayRegistry(
                gateways: $app->tagged(self::GATEWAY_TAG),
                defaultGateway: $defaultGateway,
            );
        });

        $this->app->singleton(PaymentService::class);
    }
}
