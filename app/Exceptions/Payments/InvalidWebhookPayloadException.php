<?php

namespace App\Exceptions\Payments;

use Throwable;

final class InvalidWebhookPayloadException extends PaymentException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('The payment webhook payload is invalid.', previous: $previous);
    }
}
