<?php

namespace App\Providers;

use App\Payments\Gateways\PaystackGateway;
use App\Payments\Gateways\StripeGateway;
use App\Payments\PaymentGatewayRegistry;
use App\Payments\PaymentService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Stripe\ApiRequestor;
use Stripe\HttpClient\CurlClient;
use Stripe\StripeClient;

final class PaymentServiceProvider extends ServiceProvider
{
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

        $this->app->singleton(StripeClient::class, static function (): StripeClient {
            $httpClient = new CurlClient;
            $httpClient->setTimeout(config()->integer('payments.gateways.stripe.timeout'));
            $httpClient->setConnectTimeout(config()->integer('payments.gateways.stripe.connect_timeout'));
            ApiRequestor::setHttpClient($httpClient);
            $secret = config('payments.gateways.stripe.secret_key');

            return new StripeClient([
                'api_key' => is_string($secret) && $secret !== '' ? $secret : null,
                'stripe_version' => config('payments.gateways.stripe.api_version'),
                'max_network_retries' => config()->integer('payments.gateways.stripe.max_network_retries'),
            ]);
        });

        $this->app->singleton(StripeGateway::class, static fn (Application $app): StripeGateway => new StripeGateway(
            client: $app->make(StripeClient::class),
            secretKey: (string) config('payments.gateways.stripe.secret_key', ''),
            webhookSecret: (string) config('payments.gateways.stripe.webhook_secret', ''),
        ));

        $this->app->tag([PaystackGateway::class, StripeGateway::class], self::GATEWAY_TAG);

        $this->app->singleton(PaymentGatewayRegistry::class, static fn (Application $app): PaymentGatewayRegistry => new PaymentGatewayRegistry(
            gateways: $app->tagged(self::GATEWAY_TAG),
        ));

        $this->app->singleton(PaymentService::class);
    }
}
