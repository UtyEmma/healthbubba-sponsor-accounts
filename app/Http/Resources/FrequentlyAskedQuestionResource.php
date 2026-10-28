<?php

namespace App\Http\Resources;

use App\Models\FrequentlyAskedQuestion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin FrequentlyAskedQuestion */
final class FrequentlyAskedQuestionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'accountType' => $this->account_type->value,
            'question' => $this->question,
            'answer' => $this->answer,
        ];
    }
}
