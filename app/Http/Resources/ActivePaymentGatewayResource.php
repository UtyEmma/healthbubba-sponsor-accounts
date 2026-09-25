<?php

namespace App\Http\Resources;

use App\Enums\Payments\PaymentGatewayName;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PaymentGatewayName */
final class ActivePaymentGatewayResource extends JsonResource
{
    /** @return array{name: string, label: string} */
    public function toArray(Request $request): array
    {
        /** @var PaymentGatewayName $gateway */
        $gateway = $this->resource;

        return [
            'name' => $gateway->value,
            'label' => $gateway->label(),
        ];
    }
}
