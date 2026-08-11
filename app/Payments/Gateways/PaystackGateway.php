<?php

namespace App\Payments\Gateways;

use App\Contracts\Payments\RecurringPaymentGateway;
use App\DTOs\Payments\CheckoutSession;
use App\DTOs\Payments\GatewayWebhook;
use App\DTOs\Payments\InitializePaymentData;
use App\DTOs\Payments\PaymentMethodData;
use App\DTOs\Payments\PaymentVerification;
use App\DTOs\Payments\RecurringChargeData;
use App\Enums\Payments\PaymentGatewayName;
use App\Enums\Payments\PaymentStatus;
use App\Exceptions\Payments\GatewayConfigurationException;
use App\Exceptions\Payments\GatewayRequestException;
use App\Exceptions\Payments\InvalidWebhookPayloadException;
use App\Exceptions\Payments\InvalidWebhookSignatureException;
use App\ValueObjects\Money;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use JsonException;
use Throwable;

final readonly class PaystackGateway implements RecurringPaymentGateway
{
    public function __construct(
        private string $secretKey,
        private string $baseUrl,
        private int $timeoutSeconds = 10,
        private int $connectTimeoutSeconds = 3,
        private int $verifyAttempts = 3,
        private int $retryDelayMilliseconds = 200,
    ) {
        if (filter_var($this->baseUrl, FILTER_VALIDATE_URL) === false) {
            throw new GatewayConfigurationException('The Paystack API base URL is invalid.');
        }

        $scheme = parse_url($this->baseUrl, PHP_URL_SCHEME);

        if (! is_string($scheme) || mb_strtolower($scheme) !== 'https') {
            throw new GatewayConfigurationException('The Paystack API base URL must use HTTPS.');
        }

        if ($this->timeoutSeconds <= 0 || $this->connectTimeoutSeconds <= 0) {
            throw new GatewayConfigurationException('Paystack HTTP timeouts must be greater than zero.');
        }

        if ($this->verifyAttempts <= 0 || $this->retryDelayMilliseconds < 0) {
            throw new GatewayConfigurationException('Paystack retry settings are invalid.');
        }
    }

    public function name(): PaymentGatewayName
    {
        return PaymentGatewayName::PAYSTACK;
    }

    public function initialize(InitializePaymentData $data): CheckoutSession
    {
        $payload = $this->post('/transaction/initialize', $this->removeNullValues([
            'amount' => (string) $data->amount->amountInMinorUnits,
            'email' => $data->email,
            'currency' => $data->amount->currency,
            'reference' => $data->reference,
            'callback_url' => $data->callbackUrl,
            'metadata' => $this->encodeMetadata($data->metadata, $data->reference),
            'channels' => $data->channels,
        ]), $data->reference);

        $reference = $this->requiredString($payload, 'reference', $data->reference);

        if (! hash_equals($data->reference, $reference)) {
            throw $this->invalidResponse('Paystack returned an unexpected transaction reference.', $data->reference);
        }

        $authorizationUrl = $this->requiredString($payload, 'authorization_url', $reference);

        if (filter_var($authorizationUrl, FILTER_VALIDATE_URL) === false) {
            throw $this->invalidResponse('Paystack returned an invalid authorization URL.', $reference);
        }

        return new CheckoutSession(
            gateway: $this->name(),
            reference: $reference,
            authorizationUrl: $authorizationUrl,
            accessCode: $this->requiredString($payload, 'access_code', $reference),
        );
    }

    public function verify(string $reference): PaymentVerification
    {
        $payload = $this->get('/transaction/verify/'.rawurlencode($reference), $reference);

        return $this->mapVerification($payload, $reference);
    }

    public function charge(RecurringChargeData $data): PaymentVerification
    {
        $payload = $this->post('/transaction/charge_authorization', $this->removeNullValues([
            'amount' => (string) $data->amount->amountInMinorUnits,
            'email' => $data->email,
            'authorization_code' => $data->authorizationCode,
            'reference' => $data->reference,
            'currency' => $data->amount->currency,
            'metadata' => $this->encodeMetadata($data->metadata, $data->reference),
        ]), $data->reference);

        return $this->mapVerification($payload, $data->reference, $data);
    }

    public function parseWebhook(string $rawPayload, array $headers): GatewayWebhook
    {
        $this->ensureConfigured();

        $signature = $this->webhookSignature($headers);

        if ($signature === null || trim($signature) === '') {
            throw new InvalidWebhookSignatureException;
        }

        $expectedSignature = hash_hmac('sha512', $rawPayload, $this->secretKey);

        if (! hash_equals($expectedSignature, mb_strtolower(trim($signature)))) {
            throw new InvalidWebhookSignatureException;
        }

        try {
            $payload = json_decode(
                $rawPayload,
                true,
                512,
                JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING,
            );
        } catch (JsonException $exception) {
            throw new InvalidWebhookPayloadException($exception);
        }

        if (! is_array($payload) || ! is_string($payload['event'] ?? null) || ! is_array($payload['data'] ?? null)) {
            throw new InvalidWebhookPayloadException;
        }

        $data = $this->sanitizeProviderData($payload['data']);
        $reference = $this->optionalString($payload['data'], 'reference');

        return new GatewayWebhook(
            gateway: $this->name(),
            event: $payload['event'],
            reference: $reference,
            paymentStatus: $this->webhookPaymentStatus($payload['event']),
            data: $data,
        );
    }

    /** @param array<string, mixed> $payload */
    private function mapVerification(
        array $payload,
        string $expectedReference,
        ?RecurringChargeData $fallback = null,
    ): PaymentVerification {
        $reference = $this->optionalString($payload, 'reference') ?? $fallback?->reference;

        if ($reference === null || ! hash_equals($expectedReference, $reference)) {
            throw $this->invalidResponse('Paystack returned an unexpected transaction reference.', $expectedReference);
        }

        $amount = $this->moneyFromPayload($payload, $fallback, $reference);
        $customerEmail = $this->customerEmail($payload, $fallback, $reference);

        if ($fallback !== null
            && (! $amount->equals($fallback->amount)
                || ! hash_equals(
                    mb_strtolower(trim($fallback->email)),
                    mb_strtolower(trim($customerEmail)),
                ))) {
            throw $this->invalidResponse('Paystack returned unexpected recurring charge details.', $reference);
        }

        $providerStatus = $this->optionalString($payload, 'status')
            ?? (($payload['paused'] ?? false) === true ? 'processing' : 'pending');

        return new PaymentVerification(
            gateway: $this->name(),
            reference: $reference,
            status: $this->mapStatus($providerStatus),
            amount: $amount,
            customerEmail: $customerEmail,
            providerTransactionId: $this->optionalScalarString($payload, 'id'),
            paidAt: $this->paidAt($payload),
            paymentMethod: $this->paymentMethod($payload, $customerEmail),
            authorizationUrl: $this->optionalString($payload, 'authorization_url'),
            gatewayResponse: $this->optionalString($payload, 'gateway_response'),
            metadata: $this->metadata($payload['metadata'] ?? null),
            providerData: $this->sanitizeProviderData($payload),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function removeNullValues(array $payload): array
    {
        return array_filter(
            $payload,
            static fn (mixed $value): bool => $value !== null && $value !== [],
        );
    }

    /** @param array<string, mixed> $metadata */
    private function encodeMetadata(array $metadata, string $reference): ?string
    {
        if ($metadata === []) {
            return null;
        }

        try {
            return json_encode($metadata, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new GatewayRequestException(
                gateway: $this->name(),
                message: 'Payment metadata could not be encoded.',
                reference: $reference,
                previous: $exception,
            );
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function post(string $endpoint, array $payload, string $reference): array
    {
        try {
            $response = $this->request()->post($endpoint, $payload);
        } catch (ConnectionException|RequestException $exception) {
            throw $this->connectionFailure($reference, $exception);
        }

        return $this->responseData($response, $reference);
    }

    /** @return array<string, mixed> */
    private function get(string $endpoint, string $reference): array
    {
        try {
            $response = $this->request(retry: true)->get($endpoint);
        } catch (ConnectionException|RequestException $exception) {
            throw $this->connectionFailure($reference, $exception);
        }

        return $this->responseData($response, $reference);
    }

    private function request(bool $retry = false): PendingRequest
    {
        $this->ensureConfigured();

        $request = Http::baseUrl(rtrim($this->baseUrl, '/'))
            ->acceptJson()
            ->asJson()
            ->withToken($this->secretKey)
            ->connectTimeout($this->connectTimeoutSeconds)
            ->timeout($this->timeoutSeconds);

        if (! $retry || $this->verifyAttempts === 1) {
            return $request;
        }

        return $request->retry(
            $this->verifyAttempts,
            $this->retryDelayMilliseconds,
            static function (Throwable $exception, PendingRequest $request, ?string $method): bool {
                return $method === 'GET'
                    && ($exception instanceof ConnectionException
                        || ($exception instanceof RequestException && $exception->response->serverError()));
            },
            false,
        );
    }

    /** @return array<string, mixed> */
    private function responseData(Response $response, string $reference): array
    {
        try {
            $payload = json_decode(
                $response->body(),
                true,
                512,
                JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING,
            );
        } catch (JsonException $exception) {
            throw new GatewayRequestException(
                gateway: $this->name(),
                message: 'Paystack returned an invalid response.',
                reference: $reference,
                httpStatus: $response->status(),
                previous: $exception,
            );
        }

        if (! is_array($payload)) {
            throw $this->invalidResponse('Paystack returned an invalid response.', $reference, $response->status());
        }

        if (! $response->successful() || ($payload['status'] ?? null) !== true) {
            $message = is_string($payload['message'] ?? null)
                ? $payload['message']
                : 'Paystack could not process the payment request.';

            throw new GatewayRequestException(
                gateway: $this->name(),
                message: $message,
                reference: $reference,
                httpStatus: $response->status(),
            );
        }

        if (! is_array($payload['data'] ?? null)) {
            throw $this->invalidResponse('Paystack returned an invalid payment payload.', $reference, $response->status());
        }

        return $payload['data'];
    }

    /** @param array<string, mixed> $payload */
    private function moneyFromPayload(
        array $payload,
        ?RecurringChargeData $fallback,
        string $reference,
    ): Money {
        $amount = $this->optionalNonNegativeInteger($payload, 'amount');
        $currency = $this->optionalString($payload, 'currency');

        if ($amount === null || $currency === null) {
            if ($fallback !== null && ($payload['paused'] ?? false) === true) {
                return $fallback->amount;
            }

            throw $this->invalidResponse('Paystack omitted the payment amount or currency.', $reference);
        }

        return new Money($amount, $currency);
    }

    /** @param array<string, mixed> $payload */
    private function customerEmail(
        array $payload,
        ?RecurringChargeData $fallback,
        string $reference,
    ): string {
        $customer = $payload['customer'] ?? null;
        $email = is_array($customer) ? $this->optionalString($customer, 'email') : null;

        if ($email !== null) {
            return $email;
        }

        if ($fallback !== null) {
            return $fallback->email;
        }

        throw $this->invalidResponse('Paystack omitted the customer email.', $reference);
    }

    /** @param array<string, mixed> $payload */
    private function paymentMethod(array $payload, string $customerEmail): ?PaymentMethodData
    {
        $authorization = $payload['authorization'] ?? null;

        if (! is_array($authorization)) {
            return null;
        }

        $authorizationCode = $this->optionalString($authorization, 'authorization_code');

        if ($authorizationCode === null) {
            return null;
        }

        $customer = $payload['customer'] ?? null;
        $customerCode = is_array($customer) ? $this->optionalString($customer, 'customer_code') : null;

        return new PaymentMethodData(
            authorizationCode: $authorizationCode,
            email: $customerEmail,
            customerCode: $customerCode,
            channel: $this->optionalString($authorization, 'channel')
                ?? $this->optionalString($payload, 'channel')
                ?? 'unknown',
            reusable: ($authorization['reusable'] ?? false) === true,
            signature: $this->optionalString($authorization, 'signature'),
            cardType: $this->optionalString($authorization, 'card_type'),
            lastFour: $this->optionalString($authorization, 'last4'),
            expiryMonth: $this->optionalString($authorization, 'exp_month'),
            expiryYear: $this->optionalString($authorization, 'exp_year'),
            bank: $this->optionalString($authorization, 'bank'),
            countryCode: $this->optionalString($authorization, 'country_code'),
            accountName: $this->optionalString($authorization, 'account_name'),
            authorizationData: $this->authorizationData($authorization),
        );
    }

    private function mapStatus(string $status): PaymentStatus
    {
        return match (mb_strtolower($status)) {
            'success' => PaymentStatus::SUCCEEDED,
            'failed', 'abandoned', 'reversed', 'cancelled' => PaymentStatus::FAILED,
            'pending' => PaymentStatus::PENDING,
            default => PaymentStatus::PROCESSING,
        };
    }

    private function webhookPaymentStatus(string $event): ?PaymentStatus
    {
        return match (mb_strtolower($event)) {
            'charge.success' => PaymentStatus::SUCCEEDED,
            'charge.failed' => PaymentStatus::FAILED,
            default => null,
        };
    }

    /** @param array<string, mixed> $payload */
    private function paidAt(array $payload): ?CarbonImmutable
    {
        $paidAt = $this->optionalString($payload, 'paid_at')
            ?? $this->optionalString($payload, 'transaction_date');

        if ($paidAt === null) {
            return null;
        }

        try {
            return CarbonImmutable::parse($paidAt);
        } catch (Throwable) {
            return null;
        }
    }

    /** @return array<string, mixed> */
    private function metadata(mixed $metadata): array
    {
        if (is_array($metadata)) {
            return $metadata;
        }

        if (! is_string($metadata) || trim($metadata) === '') {
            return [];
        }

        try {
            $decoded = json_decode($metadata, true, 512, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);

            return is_array($decoded) ? $decoded : ['value' => $decoded];
        } catch (JsonException) {
            return ['value' => $metadata];
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, bool|int|string|null>
     */
    private function sanitizeProviderData(array $data): array
    {
        return $this->scalarValues(Arr::only($data, [
            'id',
            'domain',
            'status',
            'reference',
            'amount',
            'currency',
            'gateway_response',
            'message',
            'channel',
            'fees',
            'paid_at',
            'created_at',
            'transaction_date',
            'paused',
        ]));
    }

    /**
     * @param  array<array-key, mixed>  $authorization
     * @return array<string, mixed>
     */
    private function authorizationData(array $authorization): array
    {
        $authorizationData = [];

        foreach ($authorization as $key => $value) {
            if (is_string($key)) {
                $authorizationData[$key] = $value;
            }
        }

        return $authorizationData;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, bool|int|string|null>
     */
    private function scalarValues(array $values): array
    {
        return array_filter(
            $values,
            static fn (mixed $value): bool => is_bool($value)
                || is_int($value)
                || is_string($value)
                || $value === null,
        );
    }

    /** @param array<string, mixed> $payload */
    private function requiredString(array $payload, string $key, string $reference): string
    {
        return $this->optionalString($payload, $key)
            ?? throw $this->invalidResponse("Paystack omitted [{$key}] from its response.", $reference);
    }

    /** @param array<string, mixed> $payload */
    private function optionalString(array $payload, string $key): ?string
    {
        $value = $payload[$key] ?? null;

        return is_string($value) && trim($value) !== '' ? $value : null;
    }

    /** @param array<string, mixed> $payload */
    private function optionalScalarString(array $payload, string $key): ?string
    {
        $value = $payload[$key] ?? null;

        return is_int($value) || is_string($value) ? (string) $value : null;
    }

    /** @param array<string, mixed> $payload */
    private function optionalNonNegativeInteger(array $payload, string $key): ?int
    {
        $value = $payload[$key] ?? null;

        if (is_int($value) && $value >= 0) {
            return $value;
        }

        if (is_string($value) && ctype_digit($value) && (string) (int) $value === ltrim($value, '0')) {
            return (int) $value;
        }

        if ($value === '0') {
            return 0;
        }

        return null;
    }

    private function connectionFailure(string $reference, Throwable $exception): GatewayRequestException
    {
        $status = $exception instanceof RequestException ? $exception->response->status() : null;

        return new GatewayRequestException(
            gateway: $this->name(),
            message: 'Paystack is currently unavailable. Please try again.',
            reference: $reference,
            httpStatus: $status,
            previous: $exception,
        );
    }

    private function invalidResponse(
        string $message,
        string $reference,
        ?int $httpStatus = null,
    ): GatewayRequestException {
        return new GatewayRequestException(
            gateway: $this->name(),
            message: $message,
            reference: $reference,
            httpStatus: $httpStatus,
        );
    }

    private function ensureConfigured(): void
    {
        if (trim($this->secretKey) === '') {
            throw new GatewayConfigurationException('The Paystack secret key is not configured.');
        }
    }

    /** @param array<string, mixed> $headers */
    private function webhookSignature(array $headers): ?string
    {
        foreach ($headers as $name => $value) {
            if (mb_strtolower($name) !== 'x-paystack-signature') {
                continue;
            }

            if (is_string($value)) {
                return $value;
            }

            if (is_array($value) && is_string($value[0] ?? null)) {
                return $value[0];
            }
        }

        return null;
    }
}
