import { Form } from '@inertiajs/react';
import { TriangleAlertIcon } from 'lucide-react';
import { useEffect, useState } from 'react';

import StoreSubscriptionRenewalController from '@/actions/App/Http/Controllers/Payments/StoreSubscriptionRenewalController';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type {
    BillingWallet,
    SubscriptionPaymentSource,
    SubscriptionSummary,
} from '@/types';
import { PaymentSourceOptions } from './payment-source-options';

function formatMoney(amount: number, currency: string): string {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency,
        maximumFractionDigits: 2,
    }).format(amount);
}

export function SubscriptionRenewalDialog({
    subscription,
    wallet,
    open,
    onOpenChange,
}: {
    subscription: SubscriptionSummary;
    wallet: BillingWallet;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const amount = Number(subscription.renewalAmount);
    const balance = Number(wallet.balance);
    const [paymentSource, setPaymentSource] =
        useState<SubscriptionPaymentSource>('wallet');

    useEffect(() => {
        if (open) {
            setPaymentSource(balance >= amount ? 'wallet' : 'paystack');
        }
    }, [amount, balance, open]);

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent showCloseButton={false} className="sm:max-w-lg">
                <DialogHeader className="gap-1 border-b px-6 pt-6 pb-4">
                    <DialogTitle className="text-base font-semibold">
                        Renew {subscription.plan?.name ?? 'subscription'}
                    </DialogTitle>
                    <DialogDescription>
                        Choose how to pay the amount due and restore or continue
                        your subscription.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...StoreSubscriptionRenewalController.form(
                        subscription.id,
                    )}
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-5 px-6 py-5">
                                <div className="flex items-center justify-between rounded-lg border bg-muted/30 p-4 text-sm">
                                    <span className="text-muted-foreground">
                                        Renewal amount
                                    </span>
                                    <span className="text-lg font-semibold">
                                        {formatMoney(amount, wallet.currency)}
                                    </span>
                                </div>

                                <PaymentSourceOptions
                                    amount={amount}
                                    balance={balance}
                                    currency={wallet.currency}
                                    value={paymentSource}
                                    onChange={setPaymentSource}
                                    disabled={processing}
                                />

                                <div className="flex items-start gap-3 rounded-lg border border-warning/30 bg-warning/10 p-4 text-sm leading-5">
                                    <TriangleAlertIcon className="mt-0.5 size-4 shrink-0 text-warning" />
                                    <p>
                                        Payment renews this plan immediately.
                                        Future automatic renewals try Wallet
                                        before a saved Paystack authorization.
                                    </p>
                                </div>

                                <label className="flex items-start gap-3 rounded-lg border p-4 text-sm leading-5">
                                    <input
                                        type="checkbox"
                                        name="confirmed"
                                        value="1"
                                        required
                                        disabled={processing}
                                        className="mt-0.5 size-4 accent-primary"
                                    />
                                    <span>
                                        I confirm this renewal and the displayed
                                        charge.
                                    </span>
                                </label>

                                {(errors.renewal ||
                                    errors.confirmed ||
                                    errors.payment_source) && (
                                    <p
                                        className="text-sm text-destructive"
                                        role="alert"
                                    >
                                        {errors.renewal ??
                                            errors.confirmed ??
                                            errors.payment_source}
                                    </p>
                                )}
                            </div>

                            <DialogFooter className="flex-row justify-end border-t px-6 py-4">
                                <DialogClose
                                    disabled={processing}
                                    render={
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="compact"
                                        />
                                    }
                                >
                                    Cancel
                                </DialogClose>
                                <Button
                                    type="submit"
                                    size="compact"
                                    disabled={processing}
                                >
                                    {processing
                                        ? 'Processing…'
                                        : paymentSource === 'wallet'
                                          ? 'Pay and renew'
                                          : 'Continue to Paystack'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
