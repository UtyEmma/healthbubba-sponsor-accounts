import { Form } from '@inertiajs/react';
import { useEffect, useState } from 'react';

import { store as storePlanCheckout } from '@/actions/App/Http/Controllers/Payments/StorePlanCheckoutController';
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
import { Input } from '@/components/ui/input';
import type {
    AccountType,
    BillingWallet,
    Plan,
    SubscriptionPaymentSource,
} from '@/types';
import { PaymentSourceOptions } from './payment-source-options';

function formatMoney(amount: number, currency: string): string {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency,
        maximumFractionDigits: 2,
    }).format(amount);
}

export function PlanCheckoutDialog({
    accountType,
    wallet,
    plan,
    open,
    onOpenChange,
}: {
    accountType: AccountType;
    wallet: BillingWallet;
    plan: Plan | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const [additionalCapacity, setAdditionalCapacity] = useState(0);
    const [paymentSource, setPaymentSource] =
        useState<SubscriptionPaymentSource>('wallet');

    function handleOpenChange(nextOpen: boolean) {
        if (!nextOpen) {
            setAdditionalCapacity(0);
            setPaymentSource('wallet');
        }

        onOpenChange(nextOpen);
    }

    const capacity = plan?.capacity;
    const capacityEnabled = capacity?.purchases_enabled === true;
    const additionalUnitPrice = Number(capacity?.additional_unit_price ?? 0);
    const maximumAdditional =
        capacity?.maximum === null || capacity?.maximum === undefined
            ? 100000
            : Math.max(0, capacity.maximum - capacity.included);
    const total =
        Number(plan?.price ?? 0) + additionalCapacity * additionalUnitPrice;
    const accountLabel =
        accountType === 'business' ? 'business subscription' : 'subscription';
    const walletBalance = Number(wallet.balance);

    useEffect(() => {
        if (!open) {
            return;
        }

        setPaymentSource(walletBalance >= total ? 'wallet' : 'paystack');
    }, [open, plan?.id, total, walletBalance]);

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent showCloseButton={false} className="sm:max-w-lg">
                <DialogHeader className="gap-1 border-b px-6 pt-6 pb-4">
                    <DialogTitle className="text-base leading-6 font-semibold">
                        Checkout {plan?.name ?? 'plan'}
                    </DialogTitle>
                    <DialogDescription className="leading-5">
                        Review your recurring {accountLabel}, choose a payment
                        method, and confirm the charge.
                    </DialogDescription>
                </DialogHeader>

                {plan && (
                    <Form {...storePlanCheckout.form(plan.slug)}>
                        {({ errors, processing }) => (
                            <>
                                <div className="grid gap-5 px-6 py-5">
                                    <section
                                        className="grid gap-3 rounded-lg border bg-muted/30 p-4"
                                        aria-label="Order summary"
                                    >
                                        <div className="flex items-start justify-between gap-4">
                                            <div>
                                                <p className="font-medium">
                                                    {plan.name}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {plan.cadence}
                                                </p>
                                            </div>
                                            <p className="font-semibold">
                                                {formatMoney(
                                                    Number(plan.price),
                                                    plan.currency,
                                                )}
                                            </p>
                                        </div>

                                        {capacity && capacityEnabled && (
                                            <div className="grid gap-3 border-t pt-3">
                                                <div className="flex items-center justify-between gap-4 text-sm">
                                                    <span className="text-muted-foreground">
                                                        Included{' '}
                                                        {capacity.unit_plural}
                                                    </span>
                                                    <span className="font-medium">
                                                        {capacity.included}
                                                    </span>
                                                </div>
                                                <label
                                                    htmlFor="additional-capacity"
                                                    className="grid gap-1.5 text-[13px] leading-[18px] font-medium"
                                                >
                                                    Additional{' '}
                                                    {capacity.unit_plural}
                                                    <Input
                                                        id="additional-capacity"
                                                        name="additional_capacity"
                                                        type="number"
                                                        min="0"
                                                        max={maximumAdditional}
                                                        step="1"
                                                        inputMode="numeric"
                                                        value={
                                                            additionalCapacity
                                                        }
                                                        onChange={(event) =>
                                                            setAdditionalCapacity(
                                                                Math.min(
                                                                    maximumAdditional,
                                                                    Math.max(
                                                                        0,
                                                                        Number.parseInt(
                                                                            event
                                                                                .target
                                                                                .value ||
                                                                                '0',
                                                                            10,
                                                                        ),
                                                                    ),
                                                                ),
                                                            )
                                                        }
                                                        disabled={processing}
                                                        aria-invalid={Boolean(
                                                            errors.additional_capacity,
                                                        )}
                                                        aria-describedby={
                                                            errors.additional_capacity
                                                                ? 'additional-capacity-error'
                                                                : 'additional-capacity-help'
                                                        }
                                                    />
                                                </label>
                                                <p
                                                    id="additional-capacity-help"
                                                    className="text-xs text-muted-foreground"
                                                >
                                                    {formatMoney(
                                                        additionalUnitPrice,
                                                        plan.currency,
                                                    )}{' '}
                                                    per additional{' '}
                                                    {capacity.unit},{' '}
                                                    {plan.cadence}.
                                                </p>
                                                {errors.additional_capacity && (
                                                    <p
                                                        id="additional-capacity-error"
                                                        className="text-sm text-destructive"
                                                        role="alert"
                                                    >
                                                        {
                                                            errors.additional_capacity
                                                        }
                                                    </p>
                                                )}
                                                <div className="flex items-center justify-between gap-4 border-t pt-3 text-sm">
                                                    <span className="text-muted-foreground">
                                                        Total{' '}
                                                        {capacity.unit_plural}
                                                    </span>
                                                    <span className="font-medium">
                                                        {capacity.included +
                                                            additionalCapacity}
                                                    </span>
                                                </div>
                                            </div>
                                        )}

                                        <div className="flex items-end justify-between gap-4 border-t pt-3">
                                            <span className="text-sm font-medium">
                                                Total {plan.cadence}
                                            </span>
                                            <span className="text-xl font-semibold">
                                                {formatMoney(
                                                    total,
                                                    plan.currency,
                                                )}
                                            </span>
                                        </div>
                                    </section>

                                    <PaymentSourceOptions
                                        amount={total}
                                        balance={walletBalance}
                                        currency={wallet.currency}
                                        value={paymentSource}
                                        onChange={setPaymentSource}
                                        disabled={processing}
                                    />
                                    {errors.payment_source && (
                                        <p
                                            className="text-sm text-destructive"
                                            role="alert"
                                        >
                                            {errors.payment_source}
                                        </p>
                                    )}

                                    <label className="flex items-start gap-3 rounded-lg border p-4 text-sm leading-5">
                                        <input
                                            type="checkbox"
                                            name="recurring_consent"
                                            value="1"
                                            required
                                            disabled={processing}
                                            aria-invalid={Boolean(
                                                errors.recurring_consent,
                                            )}
                                            aria-describedby={
                                                errors.recurring_consent
                                                    ? 'recurring-consent-error'
                                                    : undefined
                                            }
                                            className="mt-0.5 size-4 shrink-0 accent-primary"
                                        />
                                        <span>
                                            I authorize recurring charges for
                                            this subscription at the displayed
                                            cadence. Renewals try my Wallet
                                            first, then a reusable Paystack
                                            authorization when available.
                                        </span>
                                    </label>
                                    {errors.recurring_consent && (
                                        <p
                                            id="recurring-consent-error"
                                            className="text-sm text-destructive"
                                            role="alert"
                                        >
                                            {errors.recurring_consent}
                                        </p>
                                    )}
                                    {(errors.payment ||
                                        errors.plan ||
                                        errors.gateway) && (
                                        <p
                                            className="text-sm text-destructive"
                                            role="alert"
                                        >
                                            {errors.payment ??
                                                errors.plan ??
                                                errors.gateway}
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
                                              ? 'Pay from Wallet'
                                              : 'Continue to Paystack'}
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
