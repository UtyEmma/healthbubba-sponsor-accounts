import { Form } from '@inertiajs/react';
import { PlusIcon } from 'lucide-react';
import { useState } from 'react';
import type { ReactNode } from 'react';

import { store as storeWalletPayment } from '@/actions/App/Http/Controllers/Payments/StoreWalletPaymentController';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';

export function AddFundsDialog() {
    const [open, setOpen] = useState(false);

    return (
        <WalletDialog
            open={open}
            onOpenChange={setOpen}
            trigger={
                <Button size="compact">
                    <PlusIcon className="size-4" />
                    Add funds
                </Button>
            }
            title="Add funds"
            description="Enter an amount, then continue to Paystack to choose a secure payment method."
        >
            <Form {...storeWalletPayment.form()}>
                {({ errors, processing }) => (
                    <>
                        <div className="grid gap-4 px-6 py-5">
                            <label
                                htmlFor="wallet-payment-amount"
                                className="grid gap-1.5 text-[13px] leading-[18px] font-medium"
                            >
                                Amount
                                <div className="relative">
                                    <span className="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-muted-foreground">
                                        ₦
                                    </span>
                                    <Input
                                        id="wallet-payment-amount"
                                        name="amount"
                                        type="number"
                                        min="100"
                                        step="0.01"
                                        inputMode="decimal"
                                        required
                                        disabled={processing}
                                        aria-invalid={Boolean(errors.amount)}
                                        aria-describedby={
                                            errors.amount
                                                ? 'wallet-payment-amount-error'
                                                : undefined
                                        }
                                        className="pl-8"
                                    />
                                </div>
                            </label>
                            {errors.amount && (
                                <p
                                    id="wallet-payment-amount-error"
                                    className="text-sm text-destructive"
                                    role="alert"
                                >
                                    {errors.amount}
                                </p>
                            )}
                            {errors.payment && (
                                <p
                                    className="text-sm text-destructive"
                                    role="alert"
                                >
                                    {errors.payment}
                                </p>
                            )}
                            <p className="text-xs leading-5 text-muted-foreground">
                                Your wallet is credited only after the payment
                                is verified.
                            </p>
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
                                    ? 'Opening checkout…'
                                    : 'Continue to payment'}
                            </Button>
                        </DialogFooter>
                    </>
                )}
            </Form>
        </WalletDialog>
    );
}

function WalletDialog({
    open,
    onOpenChange,
    trigger,
    title,
    description,
    children,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    trigger: ReactNode;
    title: string;
    description: string;
    children: ReactNode;
}) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogTrigger render={trigger as React.ReactElement} />
            <DialogContent showCloseButton={false}>
                <DialogHeader className="gap-1 border-b px-6 pt-6 pb-4">
                    <DialogTitle className="text-base leading-6 font-semibold">
                        {title}
                    </DialogTitle>
                    <DialogDescription className="leading-5">
                        {description}
                    </DialogDescription>
                </DialogHeader>
                {children}
            </DialogContent>
        </Dialog>
    );
}
