<?php

namespace App\Actions\Marketing;

use App\DTOs\Marketing\PaymentReversalData;
use App\Models\Payment;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class RecordPaymentReversalAction
{
    public function execute(PaymentReversalData $data): void
    {
        if ($data->reference === null && $data->providerReference === null) {
            throw new RuntimeException('A verified reversal event could not identify its receipt.');
        }
        DB::transaction(function () use ($data): void {
            $payment = Payment::query()->where('gateway', $data->gateway)->where(function (Builder $query) use ($data): void {
                if ($data->reference !== null) {
                    $query->where('reference', $data->reference);
                }
                if ($data->providerReference !== null) {
                    $query->orWhere('provider_reference', $data->providerReference);
                }
            })->lockForUpdate()->first();
            if ($payment === null) {
                throw new RuntimeException('The verified reversal receipt is not available yet.');
            }
            $occurredAt = CarbonImmutable::parse($data->occurredAt)->setTimezone(config('app.timezone'));
            if ($data->kind === 'refund') {
                if ($payment->refunded_at === null) {
                    $payment->forceFill(['refunded_at' => $occurredAt])->save();
                }

                return;
            }
            if ($payment->chargeback_event_at !== null && CarbonImmutable::parse($payment->chargeback_event_at)->gte($occurredAt)) {
                return;
            }
            $payment->forceFill(['charged_back_at' => $data->kind === 'reinstated' ? null : $occurredAt, 'chargeback_event_at' => $occurredAt])->save();
        }, 3);
    }
}
