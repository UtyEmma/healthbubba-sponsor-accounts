<?php

namespace App\Jobs\Payments;

use App\Actions\Payments\CompletePaymentAction;
use App\Actions\Payments\RecordRenewalFailureAction;
use App\Enums\Payments\PaymentGatewayName;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentStatus;
use App\Exceptions\Payments\PaymentVerificationFailed;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Workspace;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

final class ProcessPaymentWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 60;

    public function __construct(
        public readonly PaymentGatewayName $gateway,
        public readonly string $reference,
    ) {
        $this->onQueue('payments');
    }

    /** @return list<object> */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("payment:{$this->gateway->value}:{$this->reference}"))
                ->releaseAfter(10)
                ->expireAfter(120),
        ];
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [10, 30, 120, 300];
    }

    public function handle(
        CompletePaymentAction $action,
        RecordRenewalFailureAction $recordFailure,
    ): void {
        try {
            $payment = $action->execute(
                reference: $this->reference,
                gateway: $this->gateway,
            );
        } catch (PaymentVerificationFailed $exception) {
            report($exception);

            return;
        }

        if (! $this->isFailedRenewal($payment)) {
            return;
        }

        $subscription = Subscription::query()
            ->whereKey($payment->payable_id)
            ->where('subscribable_type', (new Workspace)->getMorphClass())
            ->where('subscribable_id', $payment->workspace_id)
            ->first();

        if ($subscription instanceof Subscription) {
            $recordFailure->execute($subscription, $payment);
        }
    }

    private function isFailedRenewal(Payment $payment): bool
    {
        return $payment->status === PaymentStatus::FAILED
            && $payment->purpose === PaymentPurpose::SUBSCRIPTION
            && $payment->payable_type === (new Subscription)->getMorphClass();
    }
}
