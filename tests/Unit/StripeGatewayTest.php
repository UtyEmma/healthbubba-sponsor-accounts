<?php

use App\DTOs\Payments\InitializePaymentData;
use App\DTOs\Payments\RecurringChargeData;
use App\Enums\Payments\PaymentCheckoutProfile;
use App\Enums\Payments\PaymentGatewayName;
use App\Enums\Payments\PaymentStatus;
use App\Exceptions\Payments\InvalidWebhookSignatureException;
use App\Payments\Gateways\StripeGateway;
use App\ValueObjects\Money;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Stripe\HttpClient\CurlClient;
use Stripe\StripeClient;

afterEach(function (): void {
    ApiRequestor::setHttpClient(CurlClient::instance());
});

it('initializes a recurring Stripe Checkout session with an idempotency key', function (): void {
    $client = new StripeFakeHttpClient([
        stripeResponse([
            'id' => 'cs_test_checkout',
            'object' => 'checkout.session',
            'url' => 'https://checkout.stripe.com/c/pay/cs_test_checkout',
        ]),
    ]);
    ApiRequestor::setHttpClient($client);
    $gateway = stripeGateway();

    $session = $gateway->initialize(new InitializePaymentData(
        amount: new Money(2_000_000, 'NGN'),
        email: 'person@example.com',
        reference: 'SUB-TEST-001',
        description: 'Premium subscription',
        callbackUrl: 'https://sponsors.test/payments/callback',
        metadata: ['payment_id' => 10, 'workspace_id' => 20, 'purpose' => 'subscription'],
        profile: PaymentCheckoutProfile::RECURRING_CARD,
    ));

    expect($session->gateway)->toBe(PaymentGatewayName::STRIPE)
        ->and($session->reference)->toBe('SUB-TEST-001')
        ->and($session->providerReference)->toBe('cs_test_checkout')
        ->and($session->authorizationUrl)->toStartWith('https://checkout.stripe.com/')
        ->and($client->requests)->toHaveCount(1)
        ->and($client->requests[0]['params']['payment_intent_data']['setup_future_usage'])->toBe('off_session')
        ->and($client->requests[0]['params']['metadata']['reference'])->toBe('SUB-TEST-001')
        ->and(implode(' ', $client->requests[0]['headers']))->toContain('Idempotency-Key: SUB-TEST-001');
});

it('verifies Stripe Checkout details and maps a reusable payment method', function (): void {
    $client = new StripeFakeHttpClient([
        stripeResponse(stripeCheckoutPayload('SUB-TEST-002')),
    ]);
    ApiRequestor::setHttpClient($client);

    $verification = stripeGateway()->verify('SUB-TEST-002', 'cs_test_verified');

    expect($verification->status)->toBe(PaymentStatus::SUCCEEDED)
        ->and($verification->amount->amountInMinorUnits)->toBe(2_000_000)
        ->and($verification->amount->currency)->toBe('NGN')
        ->and($verification->customerEmail)->toBe('person@example.com')
        ->and($verification->providerTransactionId)->toBe('pi_test_verified')
        ->and($verification->paymentMethod?->authorizationCode)->toBe('pm_test_card')
        ->and($verification->paymentMethod?->customerCode)->toBe('cus_test_customer')
        ->and($verification->paymentMethod?->reusable)->toBeTrue();
});

it('creates idempotent off-session Stripe charges for saved methods', function (): void {
    $client = new StripeFakeHttpClient([
        stripeResponse(stripePaymentIntentPayload('REN-TEST-001')),
    ]);
    ApiRequestor::setHttpClient($client);

    $verification = stripeGateway()->charge(new RecurringChargeData(
        amount: new Money(2_000_000, 'NGN'),
        email: 'person@example.com',
        authorizationCode: 'pm_test_card',
        reference: 'REN-TEST-001',
        metadata: ['payment_id' => 30, 'workspace_id' => 20, 'purpose' => 'subscription'],
        customerReference: 'cus_test_customer',
    ));

    expect($verification->status)->toBe(PaymentStatus::SUCCEEDED)
        ->and($client->requests[0]['params']['off_session'])->toBe('true')
        ->and($client->requests[0]['params']['confirm'])->toBe('true')
        ->and($client->requests[0]['params']['customer'])->toBe('cus_test_customer')
        ->and(implode(' ', $client->requests[0]['headers']))->toContain('Idempotency-Key: REN-TEST-001');
});

it('verifies signed Stripe webhooks and rejects invalid signatures', function (): void {
    $payload = json_encode([
        'id' => 'evt_test_checkout',
        'object' => 'event',
        'created' => time(),
        'data' => [
            'object' => [
                'id' => 'cs_test_webhook',
                'object' => 'checkout.session',
                'client_reference_id' => 'SUB-TEST-003',
                'metadata' => ['reference' => 'SUB-TEST-003'],
                'payment_status' => 'paid',
            ],
        ],
        'livemode' => false,
        'pending_webhooks' => 1,
        'type' => 'checkout.session.completed',
    ], JSON_THROW_ON_ERROR);
    $timestamp = time();
    $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", 'whsec_test');
    $gateway = stripeGateway();

    $webhook = $gateway->parseWebhook($payload, [
        'stripe-signature' => ["t={$timestamp},v1={$signature}"],
    ]);

    expect($webhook->gateway)->toBe(PaymentGatewayName::STRIPE)
        ->and($webhook->reference)->toBe('SUB-TEST-003')
        ->and($webhook->paymentStatus)->toBe(PaymentStatus::SUCCEEDED);

    expect(fn () => $gateway->parseWebhook($payload, [
        'stripe-signature' => ["t={$timestamp},v1=invalid"],
    ]))->toThrow(InvalidWebhookSignatureException::class);
});

function stripeGateway(): StripeGateway
{
    return new StripeGateway(
        client: new StripeClient('sk_test_fake'),
        secretKey: 'sk_test_fake',
        webhookSecret: 'whsec_test',
    );
}

/** @return array{0: string, 1: int, 2: array<string, string>} */
function stripeResponse(array $payload): array
{
    return [json_encode($payload, JSON_THROW_ON_ERROR), 200, ['request-id' => 'req_test']];
}

/** @return array<string, mixed> */
function stripeCheckoutPayload(string $reference): array
{
    return [
        'id' => 'cs_test_verified',
        'object' => 'checkout.session',
        'amount_total' => 2_000_000,
        'currency' => 'ngn',
        'customer_email' => 'person@example.com',
        'metadata' => ['reference' => $reference, 'payment_id' => '10', 'workspace_id' => '20', 'purpose' => 'subscription'],
        'payment_status' => 'paid',
        'status' => 'complete',
        'payment_intent' => stripePaymentIntentPayload($reference),
    ];
}

/** @return array<string, mixed> */
function stripePaymentIntentPayload(string $reference): array
{
    return [
        'id' => 'pi_test_verified',
        'object' => 'payment_intent',
        'amount' => 2_000_000,
        'currency' => 'ngn',
        'created' => time(),
        'customer' => [
            'id' => 'cus_test_customer',
            'object' => 'customer',
            'email' => 'person@example.com',
        ],
        'metadata' => ['reference' => $reference, 'payment_id' => '10', 'workspace_id' => '20', 'purpose' => 'subscription'],
        'payment_method' => [
            'id' => 'pm_test_card',
            'object' => 'payment_method',
            'type' => 'card',
            'billing_details' => ['address' => null, 'email' => 'person@example.com', 'name' => 'Test Person', 'phone' => null, 'tax_id' => null],
            'card' => ['brand' => 'visa', 'country' => 'NG', 'exp_month' => 12, 'exp_year' => 2030, 'last4' => '4242'],
        ],
        'status' => 'succeeded',
    ];
}

final class StripeFakeHttpClient implements ClientInterface
{
    /** @var list<array{method: string, url: string, headers: array<int, string>, params: array<string, mixed>}> */
    public array $requests = [];

    /** @param list<array{0: string, 1: int, 2: array<string, string>}> $responses */
    public function __construct(private array $responses) {}

    public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null): array
    {
        $this->requests[] = [
            'method' => $method,
            'url' => $absUrl,
            'headers' => $headers,
            'params' => $params,
        ];

        return array_shift($this->responses) ?? throw new RuntimeException('No Stripe fake response was configured.');
    }
}
