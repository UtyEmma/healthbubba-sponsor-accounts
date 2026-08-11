<?php

namespace App\Exceptions\Payments;

use Illuminate\Contracts\Debug\ShouldntReport;

final class InvalidWebhookSignatureException extends PaymentException implements ShouldntReport
{
    public function __construct()
    {
        parent::__construct('The payment webhook signature is invalid.');
    }
}
