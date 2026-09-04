<?php

namespace App\Http\Resources;

use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Wallet */
final class BillingWalletResource extends JsonResource
{
    /** @return array{balance: string, currency: string} */
    public function toArray(Request $request): array
    {
        return [
            'balance' => (string) $this->balance,
            'currency' => $this->currency,
        ];
    }
}
