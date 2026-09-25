<?php

namespace App\Http\Requests\Payments;

use App\Enums\AccountTypes;

final class StoreWalletPaymentRequest extends AuthorizedWorkspacePaymentRequest
{
    public function authorize(): bool
    {
        return parent::authorize() && $this->workspace()->type !== AccountTypes::INSTITUTION;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'amount' => [
                'required',
                'numeric',
                'decimal:0,2',
                'regex:/^(0|[1-9]\d*)(?:\.\d{1,2})?$/',
                'min:'.config('payments.wallet.min_amount', '100.00'),
                'max:'.config('payments.wallet.max_amount', '10000000.00'),
            ],
        ];
    }

    public function amount(): string
    {
        return (string) $this->validated('amount');
    }

    protected function prepareForValidation(): void
    {
        $amount = $this->input('amount');

        if (is_string($amount)) {
            $this->merge([
                'amount' => str_replace([',', ' '], '', $amount),
            ]);
        }
    }
}
