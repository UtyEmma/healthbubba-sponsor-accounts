import { Form } from '@inertiajs/react';
import { format } from 'date-fns';
import { CalendarClockIcon, TriangleAlertIcon } from 'lucide-react';
import { useState } from 'react';

import StoreSubscriptionBillingCycleController from '@/actions/App/Http/Controllers/Payments/StoreSubscriptionBillingCycleController';
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
import { useActivePaymentGateway } from '@/hooks/use-active-payment-gateway';
import type {
    BillingWallet,
    PlanBillingOption,
    SubscriptionBillingCycle,
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

export function BillingCycleChangeDialog({
    subscriptionId,
    planName,
    wallet,
    currentCycle,
    selectedOption,
    open,
    onOpenChange,
}: {
    subscriptionId: number;
    planName: string;
    wallet: BillingWallet;
    currentCycle: SubscriptionBillingCycle;
    selectedOption: PlanBillingOption | undefined;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const gateway = useActivePaymentGateway();
    const change = selectedOption?.billing_cycle_change;
    const amountDue = Number(change?.amount_due_now ?? 0);
    const walletBalance = Number(wallet.balance);
    const [selectedPaymentSource, setSelectedPaymentSource] =
        useState<SubscriptionPaymentSource | null>(null);
    const paymentSource =
        selectedPaymentSource ??
        (walletBalance >= amountDue ? 'wallet' : gateway.name);

    function handleOpenChange(nextOpen: boolean) {
        if (!nextOpen) {
            setSelectedPaymentSource(null);
        }

        onOpenChange(nextOpen);
    }

    if (!selectedOption || !change?.available) {
        return null;
    }

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent showCloseButton={false} className="sm:max-w-lg">
                <DialogHeader className="gap-1 border-b px-6 pt-6 pb-4">
                    <DialogTitle className="flex items-center gap-2 text-base leading-6 font-semibold">
                        <CalendarClockIcon className="size-4 text-primary" />
                        Upgrade billing cycle
                    </DialogTitle>
                    <DialogDescription className="leading-5">
                        Upgrade {planName} from{' '}
                        {currentCycle.label.toLowerCase()} to{' '}
                        {selectedOption.label.toLowerCase()} billing.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...StoreSubscriptionBillingCycleController.form(
                        subscriptionId,
                    )}
                >
                    {({ errors, processing }) => (
                        <>
                            <input
                                type="hidden"
                                name="billing_cycle"
                                value={selectedOption.cycle}
                            />
                            <div className="grid gap-5 px-6 py-5">
                                <dl className="grid gap-3 rounded-lg border bg-muted/30 p-4 text-sm">
                                    <SummaryRow label="Plan" value={planName} />
                                    <SummaryRow
                                        label="Current cycle"
                                        value={currentCycle.label}
                                    />
                                    <SummaryRow
                                        label="New cycle"
                                        value={selectedOption.label}
                                    />
                                    <SummaryRow
                                        label="Current period price"
                                        value={formatMoney(
                                            change.current_amount,
                                            wallet.currency,
                                        )}
                                    />
                                    <SummaryRow
                                        label="New period price"
                                        value={formatMoney(
                                            change.target_amount,
                                            wallet.currency,
                                        )}
                                    />
                                    <SummaryRow
                                        label="Difference due now"
                                        value={formatMoney(
                                            change.amount_due_now,
                                            wallet.currency,
                                        )}
                                    />
                                    <SummaryRow
                                        label="New period ends"
                                        value={
                                            change.target_ends_at
                                                ? format(
                                                      new Date(
                                                          change.target_ends_at,
                                                      ),
                                                      'do MMMM yyyy',
                                                  )
                                                : 'After payment'
                                        }
                                    />
                                </dl>

                                <PaymentSourceOptions
                                    amount={amountDue}
                                    balance={walletBalance}
                                    currency={wallet.currency}
                                    value={paymentSource}
                                    onChange={setSelectedPaymentSource}
                                    disabled={processing}
                                />

                                <div className="flex items-start gap-3 rounded-lg border border-warning/30 bg-warning/10 p-4 text-sm leading-5">
                                    <TriangleAlertIcon className="mt-0.5 size-4 shrink-0 text-warning" />
                                    <p>
                                        The full difference between the current
                                        and requested periods is charged now.
                                        The longer cycle starts immediately.
                                        Moving to a shorter cycle requires
                                        cancelling this subscription and buying
                                        a new one.
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
                                        I confirm this immediate billing-cycle
                                        upgrade and the displayed charge.
                                    </span>
                                </label>

                                {(errors.billing_cycle ||
                                    errors.payment_source ||
                                    errors.confirmed) && (
                                    <p
                                        className="text-sm text-destructive"
                                        role="alert"
                                    >
                                        {errors.billing_cycle ??
                                            errors.payment_source ??
                                            errors.confirmed}
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
                                          ? 'Pay and upgrade'
                                          : `Continue to ${gateway.label}`}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

function SummaryRow({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex items-start justify-between gap-4">
            <dt className="text-muted-foreground">{label}</dt>
            <dd className="text-right font-medium">{value}</dd>
        </div>
    );
}
