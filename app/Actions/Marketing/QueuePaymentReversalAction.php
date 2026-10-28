<?php

namespace App\Actions\Marketing;

use App\DTOs\Marketing\PaymentReversalData;
use App\DTOs\Payments\GatewayWebhook;
use App\Jobs\Marketing\RecordPaymentReversal;

final class QueuePaymentReversalAction
{
    public function execute(GatewayWebhook $webhook, string $verifiedPayload): void
    {
        $data = PaymentReversalData::fromVerifiedWebhook($webhook, $verifiedPayload);
        if ($data !== null) {
            RecordPaymentReversal::dispatch($data);
        }
    }
}
