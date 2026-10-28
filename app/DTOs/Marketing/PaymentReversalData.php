<?php

namespace App\DTOs\Marketing;

use App\DTOs\Payments\GatewayWebhook;
use App\Enums\Payments\PaymentGatewayName;
use Carbon\CarbonImmutable;

final readonly class PaymentReversalData
{
    public function __construct(public PaymentGatewayName $gateway, public string $kind, public ?string $reference, public ?string $providerReference, public string $occurredAt) {}

    public static function fromVerifiedWebhook(GatewayWebhook $webhook, string $rawPayload): ?self
    {
        $kind = match ($webhook->event) {
            'charge.refunded', 'refund.processed' => 'refund',
            'charge.dispute.created', 'charge.dispute.funds_withdrawn', 'charge.dispute.create', 'charge.dispute.remind' => 'chargeback',
            'charge.dispute.funds_reinstated' => 'reinstated',
            default => null,
        };
        if ($kind === null) {
            return null;
        }
        $payload = json_decode($rawPayload, true, 512, JSON_THROW_ON_ERROR);
        $data = $webhook->gateway === PaymentGatewayName::STRIPE ? ($payload['data']['object'] ?? []) : ($payload['data'] ?? []);
        $transaction = is_array($data['transaction'] ?? null) ? $data['transaction'] : [];
        $provider = $webhook->gateway === PaymentGatewayName::STRIPE ? ($data['payment_intent'] ?? null) : ($transaction['id'] ?? (is_scalar($data['transaction'] ?? null) ? $data['transaction'] : null));
        $reference = $webhook->gateway === PaymentGatewayName::PAYSTACK ? ($transaction['reference'] ?? $webhook->reference) : $webhook->reference;
        $timestamp = $payload['created'] ?? null;

        return new self($webhook->gateway, $kind, is_string($reference) && $reference !== '' ? $reference : null, is_scalar($provider) ? (string) $provider : null, (is_numeric($timestamp) ? CarbonImmutable::createFromTimestampUTC((int) $timestamp) : CarbonImmutable::now())->toISOString());
    }
}
