<?php

namespace App\Http\Resources;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/** @mixin Transaction */
final class TransactionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $type = $this->type->value;

        return [
            'id' => (int) $this->getKey(),
            'flow' => $this->flow->value,
            'description' => $this->meta['description'] ?? Str::of($type)->replace('_', ' ')->title()->toString(),
            'type' => Str::of($type)->replace('_', ' ')->title()->toString(),
            'occurred_at' => $this->created_at?->toISOString(),
            'amount' => $this->amount,
            'currency' => $this->currency,
        ];
    }
}
