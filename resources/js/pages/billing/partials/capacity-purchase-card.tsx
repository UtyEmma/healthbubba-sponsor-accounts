import { Form } from '@inertiajs/react';
import { MinusIcon, PlusIcon, WalletCardsIcon } from 'lucide-react';
import { useState } from 'react';

import { store as storeCapacityPurchase } from '@/actions/App/Http/Controllers/Payments/StoreCapacityPurchaseController';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { CapacityPurchaseSummary } from '@/types';
import { Disclose } from '@/components/toggle/disclose';

function formatMoney(amount: number, currency: string): string {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency,
        maximumFractionDigits: 2,
    }).format(amount);
}

export function CapacityPurchaseCard({
    summary,
}: {
    summary: CapacityPurchaseSummary;
}) {
    const [quantity, setQuantity] = useState(1);
    const [open, setOpen] = useState(false);
    const [paymentSource, setPaymentSource] = useState<'paystack' | 'wallet'>(
        'paystack',
    );
    const maximumQuantity =
        summary.maximum_capacity === null
            ? 100000
            : Math.max(0, summary.maximum_capacity - summary.current_capacity);
    const proratedUnitPrice = Number(summary.prorated_unit_price ?? 0);
    const renewalUnitPrice = Number(summary.unit_price ?? 0);
    const chargeNow = proratedUnitPrice * quantity;
    const renewalIncrease = renewalUnitPrice * quantity;
    const walletBalance = Number(summary.wallet_balance);
    const walletAvailable = walletBalance >= chargeNow;
    const additionalCapacity = Math.max(
        0,
        summary.current_capacity - summary.included_capacity,
    );

    function handleOpenChange(nextOpen: boolean) {
        if (!nextOpen) {
            setPaymentSource('paystack');
        }

        setOpen(nextOpen);
    }

    return (
        <Disclose show={summary.available} >
            <Card className="mt-7">
                <CardHeader className="gap-1 px-6 pt-6 pb-3">
                    <h2 className="text-base font-semibold">
                        {summary.unit === 'seat'
                            ? 'Seat management'
                            : 'Beneficiary management'}
                    </h2>
                    <p className="text-sm leading-5 text-muted-foreground">
                        {summary.available
                            ? `Add ${summary.unit_plural} mid-cycle. Add seats mid-cycle. You're charged a pro-rated amount up to your renewal date, and the full monthly allocation is injected immediately.`
                            : summary.unavailable_reason}
                    </p>
                </CardHeader>
                <CardContent className="grid gap-5 px-6 pt-3 pb-6">
                    <dl className="grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
                        <CapacityValue
                            label={`Current ${summary.unit_plural}`}
                            value={String(summary.current_capacity)}
                        />
                        <CapacityValue
                            label="Included with plan"
                            value={String(summary.included_capacity)}
                        />
                        <CapacityValue
                            label={`Additional ${summary.unit_plural}`}
                            value={String(additionalCapacity)}
                        />
                        <CapacityValue
                            label="Maximum"
                            value={
                                summary.maximum_capacity === null
                                    ? 'No plan limit'
                                    : String(summary.maximum_capacity)
                            }
                        />
                    </dl>

                    {summary.available && maximumQuantity > 0 && (
                        <div className="flex flex-wrap items-end gap-6 border-t pt-5">
                            <div className="grid gap-2">
                                <span className="text-sm font-medium">
                                    {summary.unit === 'seat'
                                        ? 'Seats to add'
                                        : 'Beneficiaries to add'}
                                </span>
                                <div className="flex items-center gap-4">
                                    <Button
                                        variant="outline"
                                        size="icon"
                                        aria-label={`Remove one ${summary.unit}`}
                                        disabled={quantity === 1}
                                        onClick={() =>
                                            setQuantity((current) =>
                                                Math.max(1, current - 1),
                                            )
                                        }
                                    >
                                        <MinusIcon className="size-4" />
                                    </Button>
                                    <output
                                        aria-live="polite"
                                        className="min-w-6 text-center text-lg font-semibold"
                                    >
                                        {quantity}
                                    </output>
                                    <Button
                                        variant="outline"
                                        size="icon"
                                        aria-label={`Add one ${summary.unit}`}
                                        disabled={quantity >= maximumQuantity}
                                        onClick={() =>
                                            setQuantity((current) =>
                                                Math.min(
                                                    maximumQuantity,
                                                    current + 1,
                                                ),
                                            )
                                        }
                                    >
                                        <PlusIcon className="size-4" />
                                    </Button>
                                </div>
                            </div>
                            <div className="grid gap-0.5">
                                <span className="text-sm text-muted-foreground">
                                    Prorated charge now
                                </span>
                                <output className="text-xl font-semibold">
                                    {formatMoney(chargeNow, summary.currency)}
                                </output>
                            </div>
                            <Button onClick={() => setOpen(true)}>
                                Add {quantity}{' '}
                                {quantity === 1
                                    ? summary.unit
                                    : summary.unit_plural}
                            </Button>
                        </div>
                    )}

                    {summary.available && maximumQuantity === 0 && (
                        <p className="border-t pt-4 text-sm text-muted-foreground">
                            This subscription has reached its maximum {summary.unit}{' '}
                            capacity.
                        </p>
                    )}
                </CardContent>

                <Dialog open={open} onOpenChange={handleOpenChange}>
                    <DialogContent showCloseButton={false} className="sm:max-w-lg">
                        <DialogHeader className="gap-1 border-b px-6 pt-6 pb-4">
                            <DialogTitle className="text-base leading-6 font-semibold">
                                Add {quantity}{' '}
                                {quantity === 1
                                    ? summary.unit
                                    : summary.unit_plural}
                            </DialogTitle>
                            <DialogDescription className="leading-5">
                                Choose how to pay for the prorated capacity
                                purchase.
                            </DialogDescription>
                        </DialogHeader>

                        <Form
                            {...storeCapacityPurchase.form(summary.subscription_id)}
                            onSuccess={() => setOpen(false)}
                        >
                            {({ errors, processing,  }) => (
                                <>
                                    <input
                                        type="hidden"
                                        name="quantity"
                                        value={quantity}
                                    />
                                    <div className="grid gap-5 px-6 py-5">
                                        <section className="grid gap-3 rounded-lg border bg-muted/30 p-4">
                                            <CapacityRow
                                                label="Charge now"
                                                value={formatMoney(
                                                    chargeNow,
                                                    summary.currency,
                                                )}
                                            />
                                            <CapacityRow
                                                label="Added at renewal"
                                                value={formatMoney(
                                                    renewalIncrease,
                                                    summary.currency,
                                                )}
                                            />
                                            <CapacityRow
                                                label={`New ${summary.unit} capacity`}
                                                value={String(
                                                    summary.current_capacity +
                                                        quantity,
                                                )}
                                            />
                                        </section>

                                        <fieldset className="grid gap-3">
                                            <legend className="text-sm font-medium">
                                                Payment method
                                            </legend>
                                            <PaymentOption
                                                value="paystack"
                                                checked={
                                                    paymentSource === 'paystack'
                                                }
                                                onChange={setPaymentSource}
                                                disabled={processing}
                                                title="Paystack"
                                                description="Pay securely by card through Paystack."
                                            />
                                            <PaymentOption
                                                value="wallet"
                                                checked={paymentSource === 'wallet'}
                                                onChange={setPaymentSource}
                                                disabled={
                                                    processing || !walletAvailable
                                                }
                                                title="Wallet balance"
                                                description={`${formatMoney(walletBalance, summary.currency)} available${walletAvailable ? '' : ' — insufficient balance'}`}
                                                icon={
                                                    <WalletCardsIcon className="size-4" />
                                                }
                                            />
                                        </fieldset>

                                        {(errors.capacity ||
                                            errors.quantity ||
                                            errors.payment_source) && (
                                            <p
                                                className="text-sm text-destructive"
                                                role="alert"
                                            >
                                                {errors.capacity ??
                                                    errors.quantity ??
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
                                                ? 'Pay from wallet'
                                                : 'Continue to Paystack'}
                                        </Button>
                                    </DialogFooter>
                                </>
                            )}
                        </Form>
                    </DialogContent>
                </Dialog>
            </Card>
        </Disclose>
    );
}

function CapacityValue({ label, value }: { label: string; value: string }) {
    return (
        <div className="grid gap-1">
            <dt className="text-muted-foreground">{label}</dt>
            <dd className="font-medium">{value}</dd>
        </div>
    );
}

function CapacityRow({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex items-center justify-between gap-4 text-sm">
            <span className="text-muted-foreground">{label}</span>
            <span className="font-medium">{value}</span>
        </div>
    );
}

function PaymentOption({
    value,
    checked,
    onChange,
    disabled,
    title,
    description,
    icon,
}: {
    value: 'paystack' | 'wallet';
    checked: boolean;
    onChange: (value: 'paystack' | 'wallet') => void;
    disabled: boolean;
    title: string;
    description: string;
    icon?: React.ReactNode;
}) {
    return (
        <label className="flex items-start gap-3 rounded-lg border p-4 has-checked:border-primary has-checked:bg-primary/5 has-disabled:cursor-not-allowed has-disabled:opacity-60">
            <input
                type="radio"
                name="payment_source"
                value={value}
                checked={checked}
                disabled={disabled}
                onChange={() => onChange(value)}
                className="mt-1 size-4 accent-primary"
            />
            <span className="grid gap-1">
                <span className="flex items-center gap-2 text-sm font-medium">
                    {icon}
                    {title}
                </span>
                <span className="text-xs leading-5 text-muted-foreground">
                    {description}
                </span>
            </span>
        </label>
    );
}
