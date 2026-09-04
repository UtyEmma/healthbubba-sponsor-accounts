import { Form } from '@inertiajs/react';
import { ArrowDownIcon, ArrowUpIcon, TriangleAlertIcon } from 'lucide-react';
import { useEffect, useState } from 'react';

import { store as storePlanChange } from '@/actions/App/Http/Controllers/Payments/StorePlanChangeController';
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
    Plan,
    SubscriptionPaymentSource,
} from '@/types';
import { PaymentSourceOptions } from './payment-source-options';

function formatMoney(amount: string, currency: string): string {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency,
        maximumFractionDigits: 2,
    }).format(Number(amount));
}

export function PlanChangeDialog({
    subscriptionId,
    currentPlanName,
    currentCapacity,
    wallet,
    plan,
    open,
    onOpenChange,
}: {
    subscriptionId: number;
    currentPlanName: string;
    currentCapacity: number;
    wallet: BillingWallet;
    plan: Plan | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const change = plan?.plan_change;
    const isUpgrade = change?.direction === 'upgrade';
    const amountDue = Number(change?.amount_due_now ?? 0);
    const walletBalance = Number(wallet.balance);
    const [paymentSource, setPaymentSource] =
        useState<SubscriptionPaymentSource>('wallet');

    useEffect(() => {
        if (!open || !isUpgrade) {
            setPaymentSource('wallet');

            return;
        }

        setPaymentSource(walletBalance >= amountDue ? 'wallet' : 'paystack');
    }, [amountDue, isUpgrade, open, plan?.id, walletBalance]);

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent showCloseButton={false} className="sm:max-w-lg">
                <DialogHeader className="gap-1 border-b px-6 pt-6 pb-4">
                    <DialogTitle className="flex items-center gap-2 text-base leading-6 font-semibold">
                        {isUpgrade ? (
                            <ArrowUpIcon className="size-4 text-success" />
                        ) : (
                            <ArrowDownIcon className="size-4 text-warning" />
                        )}
                        {isUpgrade ? 'Upgrade' : 'Downgrade'} to {plan?.name}
                    </DialogTitle>
                    <DialogDescription className="leading-5">
                        {isUpgrade
                            ? 'The prorated base-price difference is charged now and the new plan starts immediately after payment.'
                            : 'The lower-priced plan starts immediately. There is no charge or refund for the remaining term.'}
                    </DialogDescription>
                </DialogHeader>

                {plan && change && (
                    <Form
                        {...storePlanChange.form({
                            subscription: subscriptionId,
                            plan: plan.slug,
                        })}
                    >
                        {({ errors, processing }) => (
                            <>
                                <div className="grid gap-5 px-6 py-5">
                                    <section
                                        className="grid gap-3 rounded-lg border bg-muted/30 p-4"
                                        aria-label="Plan change summary"
                                    >
                                        <SummaryRow
                                            label="Current plan"
                                            value={currentPlanName}
                                        />
                                        <SummaryRow
                                            label="New plan"
                                            value={plan.name}
                                        />
                                        <SummaryRow
                                            label={
                                                isUpgrade
                                                    ? 'Prorated charge now'
                                                    : 'Charge now'
                                            }
                                            value={formatMoney(
                                                change.amount_due_now,
                                                plan.currency,
                                            )}
                                        />
                                        <SummaryRow
                                            label="New recurring amount"
                                            value={formatMoney(
                                                change.renewal_amount,
                                                plan.currency,
                                            )}
                                        />
                                        <SummaryRow
                                            label="Effective date"
                                            value={
                                                isUpgrade
                                                    ? 'Immediately after payment'
                                                    : 'Immediately'
                                            }
                                        />
                                        <SummaryRow
                                            label="Capacity"
                                            value={`${currentCapacity} → ${change.target_capacity_count}`}
                                        />
                                    </section>

                                    {isUpgrade && (
                                        <PaymentSourceOptions
                                            amount={amountDue}
                                            balance={walletBalance}
                                            currency={wallet.currency}
                                            value={paymentSource}
                                            onChange={setPaymentSource}
                                            disabled={processing}
                                        />
                                    )}

                                    <div className="flex items-start gap-3 rounded-lg border border-warning/30 bg-warning/10 p-4 text-sm leading-5">
                                        <TriangleAlertIcon className="mt-0.5 size-4 shrink-0 text-warning" />
                                        <p>
                                            {isUpgrade
                                                ? 'This upgrade takes effect immediately after payment. The full new price applies at renewal.'
                                                : 'This downgrade takes effect immediately and reduces your limits. No refund or Wallet credit will be issued.'}
                                        </p>
                                    </div>

                                    <label className="flex items-start gap-3 rounded-lg border p-4 text-sm leading-5">
                                        <input
                                            type="checkbox"
                                            name="confirmed"
                                            value="1"
                                            required
                                            disabled={processing}
                                            className="mt-0.5 size-4 shrink-0 accent-primary"
                                        />
                                        <span>
                                            I understand this plan change and
                                            confirm its immediate effect,
                                            limits, and displayed amount.
                                        </span>
                                    </label>

                                    {(errors.confirmed ||
                                        errors.plan_change ||
                                        errors.payment_source) && (
                                        <p
                                            className="text-sm text-destructive"
                                            role="alert"
                                        >
                                            {errors.confirmed ??
                                                errors.plan_change ??
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
                                            : isUpgrade
                                              ? paymentSource === 'wallet'
                                                  ? 'Pay and upgrade'
                                                  : 'Continue to Paystack'
                                              : 'Confirm downgrade'}
                                    </Button>
                                </DialogFooter>
                            </>
                        )}
                    </Form>
                )}
            </DialogContent>
        </Dialog>
    );
}

function SummaryRow({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex items-center justify-between gap-4 text-sm">
            <span className="text-muted-foreground">{label}</span>
            <span className="text-right font-medium">{value}</span>
        </div>
    );
}
