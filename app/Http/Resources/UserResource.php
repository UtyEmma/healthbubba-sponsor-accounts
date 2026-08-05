<?php

namespace App\Http\Resources;

use App\Enums\AccountTypes;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        return collect(parent::toArray($request))
            ->when($this->type == AccountTypes::BUSINESS, function($collection, ) {
                $wallet = $this->organization?->wallet;
                return $collection->merge([
                    'wallet' => $wallet
                ]);
            })->toArray();
    }
}
