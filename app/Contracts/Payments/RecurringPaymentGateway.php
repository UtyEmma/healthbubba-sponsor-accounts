<?php

namespace App\Contracts\Payments;

use App\DTOs\Payments\PaymentVerification;
use App\DTOs\Payments\RecurringChargeData;

interface RecurringPaymentGateway extends PaymentGateway
{
    public function charge(RecurringChargeData $data): PaymentVerification;
}
