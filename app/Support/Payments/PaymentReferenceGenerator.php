<?php

namespace App\Support\Payments;

use App\Enums\Payments\PaymentPurpose;
use Illuminate\Support\Str;

final class PaymentReferenceGenerator
{
    public function generate(PaymentPurpose $purpose): string
    {
        $prefix = match ($purpose) {
            PaymentPurpose::WALLET_TOP_UP => 'WAL',
            PaymentPurpose::SUBSCRIPTION => 'SUB',
            PaymentPurpose::CAPACITY_PURCHASE => 'CAP',
        };

        return $prefix.'-'.Str::upper((string) Str::ulid());
    }
}
