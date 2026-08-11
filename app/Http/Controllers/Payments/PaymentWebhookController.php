<?php

namespace App\Http\Controllers\Payments;

use App\Enums\Payments\PaymentGatewayName;
use App\Enums\Payments\PaymentStatus;
use App\Exceptions\Payments\InvalidWebhookPayloadException;
use App\Exceptions\Payments\InvalidWebhookSignatureException;
use App\Http\Controllers\Controller;
use App\Jobs\Payments\ProcessPaymentWebhook;
use App\Payments\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PaymentWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        string $gateway,
        PaymentService $payments,
    ): JsonResponse {
        $gatewayName = PaymentGatewayName::tryFrom($gateway);

        abort_if($gatewayName === null, 404);

        $rawPayload = $request->getContent();

        abort_if(strlen($rawPayload) > 1_000_000, 413);

        try {
            $webhook = $payments->parseWebhook(
                rawPayload: $rawPayload,
                headers: $request->headers->all(),
                gateway: $gatewayName,
            );
        } catch (InvalidWebhookSignatureException) {
            abort(401);
        } catch (InvalidWebhookPayloadException) {
            abort(400);
        }

        if ($webhook->reference !== null
            && in_array($webhook->paymentStatus, [PaymentStatus::SUCCEEDED, PaymentStatus::FAILED], true)) {
            ProcessPaymentWebhook::dispatch(
                gateway: $webhook->gateway,
                reference: $webhook->reference,
            );
        }

        return response()->json(['received' => true]);
    }
}
