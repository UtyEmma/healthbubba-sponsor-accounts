<?php

namespace App\Jobs\Marketing;

use App\Actions\Marketing\RecordPaymentReversalAction;
use App\DTOs\Marketing\PaymentReversalData;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

final class RecordPaymentReversal implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 30;

    public function __construct(public readonly PaymentReversalData $data) {}

    public function backoff(): array
    {
        return [30, 120, 300, 900];
    }

    public function handle(RecordPaymentReversalAction $action): void
    {
        $action->execute($this->data);
    }

    public function failed(?Throwable $exception): void
    {
        logger()->error('A verified payment reversal requires manual reconciliation.', ['gateway' => $this->data->gateway->value, 'kind' => $this->data->kind]);
    }
}
