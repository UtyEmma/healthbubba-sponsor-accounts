<?php

namespace App\Http\Resources;

use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Wallet */
final class WalletResource extends JsonResource
{
    public function __construct(
        Wallet $resource,
        private readonly string $totalIn,
        private readonly string $totalOut,
    ) {
        parent::__construct($resource);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'balance' => $this->balance,
            'currency' => $this->currency,
            'total_in' => $this->totalIn,
            'total_out' => $this->totalOut,
        ];
    }
}
