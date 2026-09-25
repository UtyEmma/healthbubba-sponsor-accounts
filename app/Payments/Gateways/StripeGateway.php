<?php

namespace App\Payments\Gateways;

use App\Contracts\Payments\RecurringPaymentGateway;
use App\DTOs\Payments\CheckoutSession;
use App\DTOs\Payments\GatewayWebhook;
use App\DTOs\Payments\InitializePaymentData;
use App\DTOs\Payments\PaymentMethodData;
use App\DTOs\Payments\PaymentVerification;
use App\DTOs\Payments\RecurringChargeData;
use App\Enums\Payments\PaymentCheckoutProfile;
use App\Enums\Payments\PaymentGatewayName;
use App\Enums\Payments\PaymentStatus;
use App\Exceptions\Payments\GatewayConfigurationException;
use App\Exceptions\Payments\GatewayRequestException;
use App\Exceptions\Payments\InvalidWebhookPayloadException;
use App\Exceptions\Payments\InvalidWebhookSignatureException;
use App\ValueObjects\Money;
use Carbon\CarbonImmutable;
use Stripe\Checkout\Session;
use Stripe\Customer;
use Stripe\Event;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\CardException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;
use Stripe\StripeClient;
use Stripe\Webhook;

final readonly class StripeGateway implements RecurringPaymentGateway
{
    public function __construct(
        private StripeClient $client,
        private string $secretKey,
        private string $webhookSecret,
    ) {}

    public function name(): PaymentGatewayName
    {
        return PaymentGatewayName::STRIPE;
    }

    public function initialize(InitializePaymentData $data): CheckoutSession
    {
        $this->ensureConfigured();

        $metadata = $this->metadata($data->metadata, $data->reference);
        $successUrl = $this->returnUrl($data->callbackUrl, $data->reference);
        $parameters = [
            'mode' => 'payment',
            'client_reference_id' => $data->reference,
            'success_url' => $successUrl,
            'cancel_url' => $this->returnUrl($data->callbackUrl, $data->reference, true),
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => mb_strtolower($data->amount->currency),
                    'unit_amount' => $data->amount->amountInMinorUnits,
                    'product_data' => ['name' => $data->description],
                ],
            ]],
            'metadata' => $metadata,
            'payment_intent_data' => [
                'description' => $data->description,
                'metadata' => $metadata,
            ],
        ];

        if ($data->customerReference !== null) {
            $parameters['customer'] = $data->customerReference;
        } else {
            $parameters['customer_email'] = $data->email;
        }

        if ($data->profile !== PaymentCheckoutProfile::FLEXIBLE) {
            $parameters['payment_method_types'] = ['card'];
        }

        if ($data->profile === PaymentCheckoutProfile::RECURRING_CARD) {
            $parameters['payment_intent_data']['setup_future_usage'] = 'off_session';

            if ($data->customerReference === null) {
                $parameters['customer_creation'] = 'always';
            }
        }

        try {
            $session = $this->client->checkout->sessions->create(
                $parameters,
                ['idempotency_key' => $data->reference],
            );
        } catch (ApiErrorException $exception) {
            throw $this->requestFailure($data->reference, $exception);
        }

        if (! is_string($session->url) || filter_var($session->url, FILTER_VALIDATE_URL) === false) {
            throw $this->invalidResponse('Stripe returned an invalid Checkout URL.', $data->reference);
        }

        return new CheckoutSession(
            gateway: $this->name(),
            reference: $data->reference,
            providerReference: $session->id,
            authorizationUrl: $session->url,
            accessCode: $session->id,
        );
    }

    public function verify(string $reference, ?string $providerReference = null): PaymentVerification
    {
        $this->ensureConfigured();

        if (! is_string($providerReference) || trim($providerReference) === '') {
            throw $this->invalidResponse('Stripe payment verification is missing its provider reference.', $reference);
        }

        try {
            if (str_starts_with($providerReference, 'pi_')) {
                $intent = $this->client->paymentIntents->retrieve(
                    $providerReference,
                    ['expand' => ['payment_method', 'customer']],
                );

                return $this->verificationFromIntent($intent, $reference);
            }

            $session = $this->client->checkout->sessions->retrieve(
                $providerReference,
                ['expand' => ['payment_intent.payment_method', 'payment_intent.customer', 'customer']],
            );
        } catch (ApiErrorException $exception) {
            throw $this->requestFailure($reference, $exception);
        }

        return $this->verificationFromSession($session, $reference);
    }

    public function charge(RecurringChargeData $data): PaymentVerification
    {
        $this->ensureConfigured();

        if (! is_string($data->customerReference) || trim($data->customerReference) === '') {
            throw $this->invalidResponse('Stripe recurring billing is missing its customer reference.', $data->reference);
        }

        try {
            $intent = $this->client->paymentIntents->create([
                'amount' => $data->amount->amountInMinorUnits,
                'currency' => mb_strtolower($data->amount->currency),
                'customer' => $data->customerReference,
                'payment_method' => $data->authorizationCode,
                'off_session' => true,
                'confirm' => true,
                'metadata' => $this->metadata($data->metadata, $data->reference),
            ], ['idempotency_key' => $data->reference]);

            return $this->verificationFromIntent($intent, $data->reference, $data);
        } catch (CardException $exception) {
            $intent = $exception->getError()?->payment_intent;

            if ($intent instanceof PaymentIntent) {
                return $this->verificationFromIntent($intent, $data->reference, $data);
            }

            return new PaymentVerification(
                gateway: $this->name(),
                reference: $data->reference,
                status: PaymentStatus::FAILED,
                amount: $data->amount,
                customerEmail: $data->email,
                gatewayResponse: 'Stripe could not charge the saved payment method.',
                metadata: $data->metadata,
            );
        } catch (ApiErrorException $exception) {
            throw $this->requestFailure($data->reference, $exception);
        }
    }

    public function parseWebhook(string $rawPayload, array $headers): GatewayWebhook
    {
        $this->ensureConfigured();
        $signature = $this->header($headers, 'stripe-signature');

        if ($signature === null) {
            throw new InvalidWebhookSignatureException;
        }

        try {
            $event = Webhook::constructEvent($rawPayload, $signature, $this->webhookSecret);
        } catch (\UnexpectedValueException $exception) {
            throw new InvalidWebhookPayloadException($exception);
        } catch (SignatureVerificationException) {
            throw new InvalidWebhookSignatureException;
        }

        return $this->webhook($event);
    }

    private function verificationFromSession(Session $session, string $expectedReference): PaymentVerification
    {
        $reference = $this->referenceFromObject($session, $expectedReference);
        $intent = $session->payment_intent;

        if (is_string($intent)) {
            try {
                $intent = $this->client->paymentIntents->retrieve(
                    $intent,
                    ['expand' => ['payment_method', 'customer']],
                );
            } catch (ApiErrorException $exception) {
                throw $this->requestFailure($reference, $exception);
            }
        }

        if ($intent instanceof PaymentIntent) {
            return $this->verificationFromIntent($intent, $reference, session: $session);
        }

        $email = $this->sessionEmail($session);

        return new PaymentVerification(
            gateway: $this->name(),
            reference: $reference,
            status: $session->payment_status === 'unpaid' ? PaymentStatus::PENDING : PaymentStatus::PROCESSING,
            amount: new Money((int) ($session->amount_total ?? 0), mb_strtoupper((string) $session->currency)),
            customerEmail: is_string($email) ? $email : '',
            providerTransactionId: $session->id,
            metadata: $this->objectMetadata($session),
            providerData: $this->providerData([
                'id' => $session->id,
                'status' => $session->status,
                'payment_status' => $session->payment_status,
            ]),
        );
    }

    private function verificationFromIntent(
        PaymentIntent $intent,
        string $expectedReference,
        ?RecurringChargeData $fallback = null,
        ?Session $session = null,
    ): PaymentVerification {
        $reference = $this->referenceFromObject($intent, $expectedReference);
        $customer = $intent->customer;
        $customerId = $customer instanceof Customer ? $customer->id : (is_string($customer) ? $customer : null);
        $email = $customer instanceof Customer ? $customer->email : null;
        $email ??= $this->sessionEmail($session) ?? $fallback?->email;
        $paymentMethod = $intent->payment_method;

        return new PaymentVerification(
            gateway: $this->name(),
            reference: $reference,
            status: $this->status($intent->status),
            amount: new Money(
                (int) $intent->amount,
                mb_strtoupper((string) $intent->currency),
            ),
            customerEmail: is_string($email) ? $email : '',
            providerTransactionId: $intent->id,
            paidAt: $intent->status === PaymentIntent::STATUS_SUCCEEDED
                ? CarbonImmutable::createFromTimestampUTC((int) $intent->created)
                : null,
            paymentMethod: $paymentMethod instanceof PaymentMethod
                ? $this->paymentMethod($paymentMethod, $customerId, (string) $email)
                : null,
            gatewayResponse: $this->intentErrorMessage($intent),
            metadata: $this->objectMetadata($intent),
            providerData: $this->providerData([
                'id' => $intent->id,
                'status' => $intent->status,
                'amount' => $intent->amount,
                'currency' => $intent->currency,
                'created' => $intent->created,
            ]),
        );
    }

    private function paymentMethod(PaymentMethod $method, ?string $customerId, string $email): PaymentMethodData
    {
        $card = $method->card;

        return new PaymentMethodData(
            authorizationCode: $method->id,
            email: $email,
            customerCode: $customerId,
            channel: $method->type,
            reusable: $customerId !== null,
            signature: $method->id,
            cardType: $card?->brand,
            lastFour: $card?->last4,
            expiryMonth: $card?->exp_month === null ? null : str_pad((string) $card->exp_month, 2, '0', STR_PAD_LEFT),
            expiryYear: $card?->exp_year === null ? null : (string) $card->exp_year,
            bank: null,
            countryCode: $card?->country,
            accountName: $method->billing_details->name,
            authorizationData: ['payment_method_id' => $method->id, 'customer_id' => $customerId],
        );
    }

    private function webhook(Event $event): GatewayWebhook
    {
        $object = $event->data->object;
        $reference = $this->optionalReferenceFromObject($object);
        $status = match ($event->type) {
            'checkout.session.async_payment_succeeded', 'payment_intent.succeeded' => PaymentStatus::SUCCEEDED,
            'checkout.session.async_payment_failed', 'payment_intent.payment_failed' => PaymentStatus::FAILED,
            'checkout.session.completed' => $object instanceof Session && $object->payment_status === 'paid'
                ? PaymentStatus::SUCCEEDED
                : PaymentStatus::PENDING,
            default => null,
        };

        return new GatewayWebhook(
            gateway: $this->name(),
            event: $event->type,
            reference: $reference,
            paymentStatus: $status,
            data: $this->providerData(['id' => $object->id ?? null, 'event_id' => $event->id]),
        );
    }

    private function status(string $status): PaymentStatus
    {
        return match ($status) {
            PaymentIntent::STATUS_SUCCEEDED => PaymentStatus::SUCCEEDED,
            PaymentIntent::STATUS_PROCESSING => PaymentStatus::PROCESSING,
            PaymentIntent::STATUS_CANCELED, PaymentIntent::STATUS_REQUIRES_PAYMENT_METHOD => PaymentStatus::FAILED,
            default => PaymentStatus::PENDING,
        };
    }

    /** @param array<string, mixed> $metadata
     * @return array<string, string>
     */
    private function metadata(array $metadata, string $reference): array
    {
        $result = ['reference' => $reference];

        foreach ($metadata as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $result[$key] = $value === null ? '' : (string) $value;
            }
        }

        return $result;
    }

    /** @return array<string, mixed> */
    private function objectMetadata(object $object): array
    {
        $metadata = $object->metadata ?? null;

        return is_object($metadata) && method_exists($metadata, 'toArray')
            ? $metadata->toArray()
            : (is_array($metadata) ? $metadata : []);
    }

    private function sessionEmail(?Session $session): ?string
    {
        if (! $session instanceof Session) {
            return null;
        }

        $details = $session->customer_details;

        return $details === null ? $session->customer_email : ($details->email ?? $session->customer_email);
    }

    private function intentErrorMessage(PaymentIntent $intent): ?string
    {
        $error = $intent->last_payment_error;

        if ($error === null) {
            return null;
        }

        $values = $error->toArray();
        $message = $values['message'] ?? null;

        return is_string($message) ? $message : null;
    }

    private function referenceFromObject(object $object, string $expectedReference): string
    {
        $reference = $this->optionalReferenceFromObject($object);

        if ($reference === null || ! hash_equals($expectedReference, $reference)) {
            throw $this->invalidResponse('Stripe returned an unexpected payment reference.', $expectedReference);
        }

        return $reference;
    }

    private function optionalReferenceFromObject(object $object): ?string
    {
        $metadata = $this->objectMetadata($object);
        $reference = $metadata['reference'] ?? ($object instanceof Session ? $object->client_reference_id : null);

        return is_string($reference) && trim($reference) !== '' ? $reference : null;
    }

    private function returnUrl(?string $callbackUrl, string $reference, bool $cancelled = false): string
    {
        if (! is_string($callbackUrl) || filter_var($callbackUrl, FILTER_VALIDATE_URL) === false) {
            throw $this->invalidResponse('Stripe Checkout requires a valid return URL.', $reference);
        }

        $separator = str_contains($callbackUrl, '?') ? '&' : '?';

        return $callbackUrl.$separator.http_build_query(array_filter([
            'reference' => $reference,
            'cancelled' => $cancelled ? '1' : null,
        ]));
    }

    /** @param array<string, mixed> $headers */
    private function header(array $headers, string $expected): ?string
    {
        foreach ($headers as $name => $value) {
            if (mb_strtolower($name) !== $expected) {
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

    /** @param array<string, mixed> $values
     * @return array<string, bool|int|string|null>
     */
    private function providerData(array $values): array
    {
        return array_filter(
            $values,
            static fn (mixed $value): bool => is_bool($value) || is_int($value) || is_string($value) || $value === null,
        );
    }

    private function ensureConfigured(): void
    {
        if (trim($this->secretKey) === '') {
            throw new GatewayConfigurationException('The Stripe secret key is not configured.');
        }

        if (trim($this->webhookSecret) === '') {
            throw new GatewayConfigurationException('The Stripe webhook secret is not configured.');
        }
    }

    private function requestFailure(string $reference, ApiErrorException $exception): GatewayRequestException
    {
        return new GatewayRequestException(
            gateway: $this->name(),
            message: 'Stripe is currently unable to process the payment request.',
            reference: $reference,
            httpStatus: $exception->getHttpStatus(),
            previous: $exception,
        );
    }

    private function invalidResponse(string $message, string $reference): GatewayRequestException
    {
        return new GatewayRequestException($this->name(), $message, $reference);
    }
}
