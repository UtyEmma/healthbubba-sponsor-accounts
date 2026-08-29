<?php

namespace App\Http\Controllers\Payments;

use App\Actions\Payments\CompletePaymentAction;
use App\Enums\AccountTypes;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentStatus;
use App\Exceptions\Payments\PaymentException;
use App\Exceptions\Payments\PaymentVerificationFailed;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payments\PaymentCallbackRequest;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;

final class PaymentCallbackController extends Controller
{
    public function __invoke(
        PaymentCallbackRequest $request,
        CompletePaymentAction $action,
    ): RedirectResponse {
        $payment = Payment::query()
            ->with('workspace')
            ->where('reference', $request->reference())
            ->firstOrFail();

        try {
            $payment = $action->execute(
                reference: $payment->reference,
                gateway: $payment->gateway,
            );
        } catch (PaymentException|PaymentVerificationFailed $exception) {
            report($exception);

            return redirect()->route($this->destination($payment))
                ->withErrors(['payment' => 'We could not verify this payment. No value was delivered.']);
        }

        if ($payment->status !== PaymentStatus::SUCCEEDED) {
            return redirect()->route($this->destination($payment))
                ->withErrors(['payment' => 'This payment has not been completed.']);
        }

        return redirect()->route($this->destination($payment))->with(
            'success',
            match ($payment->purpose) {
                PaymentPurpose::WALLET_TOP_UP => 'Your wallet has been funded.',
                PaymentPurpose::SUBSCRIPTION => 'Your subscription is now active.',
                PaymentPurpose::CAPACITY_PURCHASE => 'Your additional capacity is now available.',
                PaymentPurpose::PLAN_UPGRADE => 'Your plan upgrade is now active.',
            },
        );
    }

    private function destination(Payment $payment): string
    {
        if ($payment->purpose === PaymentPurpose::WALLET_TOP_UP) {
            return $payment->workspace->type === AccountTypes::INSTITUTION
                ? 'funding.index'
                : 'wallet.index';
        }

        return $payment->workspace->type === AccountTypes::BUSINESS
            ? 'business.plans'
            : 'plans.index';
    }
}
