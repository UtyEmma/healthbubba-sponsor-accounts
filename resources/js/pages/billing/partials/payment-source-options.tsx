import { CreditCardIcon, WalletIcon } from 'lucide-react';

import { cn } from '@/lib/utils';
import type { SubscriptionPaymentSource } from '@/types';

function formatMoney(amount: number, currency: string): string {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency,
        maximumFractionDigits: 2,
    }).format(amount);
}

export function PaymentSourceOptions({
    amount,
    balance,
    currency,
    value,
    onChange,
    disabled = false,
}: {
    amount: number;
    balance: number;
    currency: string;
    value: SubscriptionPaymentSource;
    onChange: (source: SubscriptionPaymentSource) => void;
    disabled?: boolean;
}) {
    const walletAvailable = balance >= amount;
    const projectedBalance = Math.max(0, balance - amount);

    return (
        <fieldset className="grid gap-3">
            <legend className="mb-1 text-sm font-medium">Payment method</legend>
            <label
                className={cn(
                    'flex cursor-pointer items-start gap-3 rounded-lg border p-4 transition-colors',
                    value === 'wallet' && 'border-primary bg-primary/5',
                    (!walletAvailable || disabled) &&
                        'cursor-not-allowed opacity-60',
                )}
            >
                <input
                    type="radio"
                    name="payment_source"
                    value="wallet"
                    checked={value === 'wallet'}
                    onChange={() => onChange('wallet')}
                    disabled={!walletAvailable || disabled}
                    className="mt-1 size-4 accent-primary"
                />
                <WalletIcon className="mt-0.5 size-5 shrink-0 text-primary" />
                <span className="grid flex-1 gap-1 text-sm">
                    <span className="flex items-center justify-between gap-3 font-medium">
                        <span>Wallet</span>
                        <span>{formatMoney(balance, currency)}</span>
                    </span>
                    <span className="text-xs text-muted-foreground">
                        {walletAvailable
                            ? `${formatMoney(projectedBalance, currency)} available after payment.`
                            : `${formatMoney(amount - balance, currency)} more is needed to use Wallet.`}
                    </span>
                </span>
            </label>

            <label
                className={cn(
                    'flex cursor-pointer items-start gap-3 rounded-lg border p-4 transition-colors',
                    value === 'paystack' && 'border-primary bg-primary/5',
                    disabled && 'cursor-not-allowed opacity-60',
                )}
            >
                <input
                    type="radio"
                    name="payment_source"
                    value="paystack"
                    checked={value === 'paystack'}
                    onChange={() => onChange('paystack')}
                    disabled={disabled}
                    className="mt-1 size-4 accent-primary"
                />
                <CreditCardIcon className="mt-0.5 size-5 shrink-0 text-primary" />
                <span className="grid gap-1 text-sm">
                    <span className="font-medium">Paystack</span>
                    <span className="text-xs text-muted-foreground">
                        Continue to secure card checkout.
                    </span>
                </span>
            </label>
        </fieldset>
    );
}
